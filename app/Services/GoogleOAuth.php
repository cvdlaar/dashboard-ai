<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GoogleOAuth
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const AUTH_URL  = 'https://accounts.google.com/o/oauth2/v2/auth';

    public static array $scopes = [
        'google_search_console' => 'https://www.googleapis.com/auth/webmasters.readonly',
        'google_analytics'      => 'https://www.googleapis.com/auth/analytics.readonly',
        'google_ads'            => 'https://www.googleapis.com/auth/adwords',
    ];

    public function authUrl(string $clientId, string $platform, string $state): string
    {
        $scope = self::$scopes[$platform] ?? self::$scopes['google_search_console'];

        return self::AUTH_URL . '?' . http_build_query([
            'client_id'             => $clientId,
            'redirect_uri'          => $this->callbackUrl(),
            'response_type'         => 'code',
            'scope'                 => $scope,
            'access_type'           => 'offline',
            'prompt'                => 'consent',  // forceert refresh_token
            'state'                 => $state,
        ]);
    }

    public function exchangeCode(string $code, string $clientId, string $clientSecret): array
    {
        $response = Http::asForm()->post(self::TOKEN_URL, [
            'code'          => $code,
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'redirect_uri'  => $this->callbackUrl(),
            'grant_type'    => 'authorization_code',
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Token exchange mislukt: ' . $response->body());
        }

        return $response->json();
    }

    public function refreshAccessToken(string $refreshToken, string $clientId, string $clientSecret): string
    {
        $response = Http::asForm()->post(self::TOKEN_URL, [
            'refresh_token' => $refreshToken,
            'client_id'     => $clientId,
            'client_secret' => $clientSecret,
            'grant_type'    => 'refresh_token',
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('Token refresh mislukt: ' . $response->body());
        }

        return $response->json('access_token');
    }

    public function callbackUrl(): string
    {
        return rtrim(config('app.url'), '/') . '/admin/oauth/google/callback';
    }
}
