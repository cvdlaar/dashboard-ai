<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Site;
use App\Models\SiteIntegration;
use App\Models\SyncLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class IntegrationController extends Controller
{
    public function index(Request $request)
    {
        $sites = Site::where('is_active', true)->get();

        // Bepaal geselecteerde site via query param, sessie, of eerste site
        $currentSiteId = $request->query('site_id')
            ?? session('current_site_id')
            ?? $sites->first()?->id;

        $currentSite = $sites->firstWhere('id', $currentSiteId) ?? $sites->first();

        if ($request->query('site_id')) {
            session(['current_site_id' => $currentSite->id]);
        }

        $currentSite->load('integrations');

        $platforms = SiteIntegration::$platforms;

        // Laatste sync-log per platform voor de huidige site
        $lastLogs = SyncLog::where('site_id', $currentSite->id)
            ->whereIn('type', array_keys($platforms))
            ->orderBy('started_at', 'desc')
            ->get()
            ->keyBy('type');

        return view('admin.integrations.index', compact('sites', 'currentSite', 'platforms', 'lastLogs'));
    }

    public function edit(Site $site, string $platform)
    {
        abort_unless(array_key_exists($platform, SiteIntegration::$platforms), 404);

        $integration = SiteIntegration::firstOrNew(
            ['site_id' => $site->id, 'platform' => $platform]
        );

        $platformConfig = SiteIntegration::$platforms[$platform];

        return view('admin.integrations.edit', compact('site', 'integration', 'platform', 'platformConfig'));
    }

    public function update(Request $request, Site $site, string $platform)
    {
        abort_unless(array_key_exists($platform, SiteIntegration::$platforms), 404);

        $integration = SiteIntegration::firstOrNew(
            ['site_id' => $site->id, 'platform' => $platform]
        );

        $platformConfig = SiteIntegration::$platforms[$platform];

        // Sync schema opslaan
        $integration->sync_schedule = $request->input('sync_schedule', 'manual');

        // Instellingen (niet-gevoelig: property ID's etc.)
        $settings = [];
        foreach ($platformConfig['fields'] as $field) {
            if ($request->filled($field) && !in_array($field, ['api_key', 'client_secret', 'feed_url'])) {
                $settings[$field] = $request->input($field);
            }
        }
        $integration->settings = $settings;

        // Credentials (gevoelig: versleuteld opslaan)
        $credFields = ['api_key', 'client_secret', 'feed_url', 'access_token', 'refresh_token'];
        $creds = [];
        foreach ($credFields as $field) {
            if ($request->filled($field) && $request->input($field) !== '••••••••') {
                $creds[$field] = $request->input($field);
            }
        }

        if (!empty($creds)) {
            $integration->setCredentials($creds);
        }

        if ($platformConfig['auth'] === 'api_key' && !empty($creds)) {
            $integration->status = 'connected';
        }

        $integration->site_id = $site->id;
        $integration->platform = $platform;
        $integration->save();

        return redirect()
            ->route('admin.integrations.index')
            ->with('success', $platformConfig['label'] . ' opgeslagen voor ' . $site->name . '.');
    }

    public function sync(Request $request, Site $site, string $platform)
    {
        abort_unless(array_key_exists($platform, SiteIntegration::$platforms), 404);

        // Schema-wijziging opslaan zonder sync te starten
        if ($request->input('_action') === 'schedule') {
            SiteIntegration::updateOrCreate(
                ['site_id' => $site->id, 'platform' => $platform],
                ['sync_schedule' => $request->input('sync_schedule', 'manual')]
            );
            return back()->with('success', 'Sync schema opgeslagen.');
        }

        $commands = [
            'google_search_console' => 'sync:gsc',
            'google_analytics'      => 'sync:ga4',
            'google_ads'            => 'sync:google-ads',
            'bing_ads'              => 'sync:bing-ads',
            'channable'             => 'import:channable',
            'pagespeed'             => 'scan:cwv',
        ];

        // Sitemap heeft geen koppeling nodig maar is wel een sync-actie
        if ($platform === 'sitemap') {
            $command = 'import:sitemap';
        } else {
            $command = $commands[$platform] ?? null;
        }

        if (!$command) {
            return back()->with('error', 'Geen sync beschikbaar voor dit platform.');
        }

        $registered = collect(Artisan::all())->keys();
        if (!$registered->contains($command)) {
            return back()->with('error', "'{$command}' is nog niet beschikbaar.");
        }

        $log = SyncLog::create([
            'site_id'    => $site->id,
            'type'       => $platform,
            'status'     => 'running',
            'started_at' => now(),
        ]);

        try {
            Artisan::call($command, ['--site' => $site->domain]);
            $output = Artisan::output();

            preg_match('/(\d+) nieuw/', $output, $newMatch);
            preg_match('/(\d+) bijgewerkt/', $output, $updatedMatch);

            $new      = (int) ($newMatch[1] ?? 0);
            $updated  = (int) ($updatedMatch[1] ?? 0);

            $log->update([
                'status'          => 'completed',
                'records_new'     => $new,
                'records_updated' => $updated,
                'message'         => trim($output),
                'finished_at'     => now(),
            ]);

            // Bijwerken op de integration zelf
            SiteIntegration::where('site_id', $site->id)
                ->where('platform', $platform)
                ->update([
                    'last_sync_at'     => now(),
                    'records_new'      => $new,
                    'records_updated'  => $updated,
                    'error_message'    => null,
                ]);

            return back()->with('success', SiteIntegration::$platforms[$platform]['label'] . ' gesynchroniseerd — ' . $new . ' nieuw, ' . $updated . ' bijgewerkt.');
        } catch (\Exception $e) {
            $log->update([
                'status'      => 'failed',
                'message'     => $e->getMessage(),
                'finished_at' => now(),
            ]);

            SiteIntegration::where('site_id', $site->id)
                ->where('platform', $platform)
                ->update(['error_message' => $e->getMessage()]);

            return back()->with('error', 'Sync mislukt: ' . $e->getMessage());
        }
    }

    public function destroy(Site $site, string $platform)
    {
        SiteIntegration::where('site_id', $site->id)
            ->where('platform', $platform)
            ->delete();

        return redirect()
            ->route('admin.integrations.index')
            ->with('success', 'Koppeling verwijderd.');
    }
}
