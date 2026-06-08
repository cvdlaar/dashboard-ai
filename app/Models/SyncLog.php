<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncLog extends Model
{
    protected $fillable = [
        'site_id', 'type', 'status', 'records_new', 'records_updated',
        'records_failed', 'message', 'started_at', 'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public static array $typeLabels = [
        'sitemap'     => 'Sitemap import',
        'channable'   => 'Channable feed',
        'pagespeed'   => 'PageSpeed / CWV',
        'gsc'         => 'Search Console',
        'ga4'         => 'Google Analytics 4',
        'google_ads'  => 'Google Ads',
        'bing_ads'    => 'Microsoft Ads',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function getDurationAttribute(): ?string
    {
        if (!$this->started_at || !$this->finished_at) return null;
        $secs = $this->started_at->diffInSeconds($this->finished_at);
        return $secs < 60 ? "{$secs}s" : floor($secs / 60) . 'm ' . ($secs % 60) . 's';
    }
}
