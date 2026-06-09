<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\SiteIntegration;
use App\Services\GoogleOAuth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class OAuthController extends Controller
{
    public function __construct(private GoogleOAuth $oauth) {}

    public function redirectToGoogle(Request $request, Site $site, string $platform)
    {
        $integration = SiteIntegration::where('site_id', $site->id)
            ->where('platform', $platform)
            ->firstOrFail();

        $creds    = json_decode($integration->credentials ?? '{}', true);
        $clientId = $creds['client_id'] ?? null;

        if (!$clientId) {
            return redirect()
                ->route('admin.integrations.edit', [$site, $platform])
                ->with('error', 'Sla eerst een Client ID op voordat je autoriseert.');
        }

        // State bevat site_id + platform (CSRF + context)
        $state = base64_encode(json_encode([
            'site_id'  => $site->id,
            'platform' => $platform,
            'csrf'     => csrf_token(),
        ]));

        session(['oauth_state' => $state]);

        return redirect($this->oauth->authUrl($clientId, $platform, $state));
    }

    public function handleGoogleCallback(Request $request)
    {
        // Valideer state
        $state = $request->query('state');
        if (!$state || $state !== session('oauth_state')) {
            return redirect()->route('admin.integrations.index')
                ->with('error', 'Ongeldige OAuth state — probeer opnieuw.');
        }

        session()->forget('oauth_state');

        $context  = json_decode(base64_decode($state), true);
        $siteId   = $context['site_id'] ?? null;
        $platform = $context['platform'] ?? null;

        if (!$siteId || !$platform) {
            return redirect()->route('admin.integrations.index')
                ->with('error', 'Onvolledige OAuth context.');
        }

        $site        = Site::findOrFail($siteId);
        $integration = SiteIntegration::where('site_id', $siteId)
            ->where('platform', $platform)
            ->firstOrFail();

        $creds = json_decode($integration->credentials ?? '{}', true);

        if ($request->has('error')) {
            return redirect()
                ->route('admin.integrations.edit', [$site, $platform])
                ->with('error', 'Autorisatie geweigerd: ' . $request->query('error'));
        }

        try {
            $tokens = $this->oauth->exchangeCode(
                $request->query('code'),
                $creds['client_id'],
                $creds['client_secret']
            );

            $creds['access_token']  = $tokens['access_token'];
            $creds['refresh_token'] = $tokens['refresh_token'] ?? $creds['refresh_token'] ?? null;
            $creds['token_type']    = $tokens['token_type'] ?? 'Bearer';

            $integration->setCredentials($creds);
            $integration->status           = 'connected';
            $integration->token_expires_at = isset($tokens['expires_in'])
                ? now()->addSeconds($tokens['expires_in'])
                : null;
            $integration->save();

            return redirect()
                ->route('admin.integrations.index')
                ->with('success', SiteIntegration::$platforms[$platform]['label'] . ' succesvol gekoppeld met ' . $site->name . '.');
        } catch (\Exception $e) {
            Log::error('Google OAuth callback fout', ['error' => $e->getMessage()]);

            return redirect()
                ->route('admin.integrations.edit', [$site, $platform])
                ->with('error', 'Autorisatie mislukt: ' . $e->getMessage());
        }
    }
}
