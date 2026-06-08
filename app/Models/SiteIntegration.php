<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteIntegration extends Model
{
    protected $fillable = [
        'site_id', 'platform', 'status', 'credentials', 'settings',
        'sync_schedule', 'records_new', 'records_updated',
        'error_message', 'last_sync_at', 'token_expires_at',
    ];

    protected $casts = [
        'credentials' => 'encrypted',
        'settings' => 'array',
        'last_sync_at' => 'datetime',
        'token_expires_at' => 'datetime',
    ];

    protected $hidden = ['credentials'];

    public static array $platforms = [
        'google_search_console' => [
            'label' => 'Google Search Console',
            'icon' => 'google',
            'auth' => 'oauth',
            'fields' => ['site_url'],
        ],
        'google_analytics' => [
            'label' => 'Google Analytics 4',
            'icon' => 'google',
            'auth' => 'oauth',
            'fields' => ['property_id'],
        ],
        'google_ads' => [
            'label' => 'Google Ads',
            'icon' => 'google',
            'auth' => 'oauth',
            'fields' => ['customer_id', 'manager_id'],
        ],
        'bing_ads' => [
            'label' => 'Microsoft Ads (Bing)',
            'icon' => 'microsoft',
            'auth' => 'oauth',
            'fields' => ['account_id', 'customer_id'],
        ],
        'channable' => [
            'label' => 'Channable Feed',
            'icon' => 'channable',
            'auth' => 'api_key',
            'fields' => ['feed_url'],
        ],
        'pagespeed' => [
            'label' => 'PageSpeed Insights',
            'icon' => 'google',
            'auth' => 'api_key',
            'fields' => ['api_key'],
        ],
        'sitemap' => [
            'label' => 'Sitemap importeren',
            'icon' => 'sitemap',
            'auth' => 'none',
            'fields' => [],
        ],
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function syncLogs()
    {
        return $this->hasMany(SyncLog::class, 'site_id', 'site_id')
            ->where('type', $this->platform);
    }

    public function latestSyncLog()
    {
        return $this->hasOne(SyncLog::class, 'site_id', 'site_id')
            ->where('type', $this->platform)
            ->latestOfMany();
    }

    public function isConnected(): bool
    {
        return $this->status === 'connected';
    }

    public function isTokenExpired(): bool
    {
        return $this->token_expires_at && $this->token_expires_at->isPast();
    }

    public function getCredential(string $key): ?string
    {
        $creds = $this->credentials ? json_decode($this->credentials, true) : [];
        return $creds[$key] ?? null;
    }

    public function setCredentials(array $data): void
    {
        $this->credentials = json_encode($data);
    }
}
