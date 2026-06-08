<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    protected $fillable = [
        'name', 'domain', 'sitemap_url', 'channable_feed_url',
        'languages', 'ga4_property_id', 'gsc_site_url',
        'google_ads_customer_id', 'bing_ads_account_id', 'is_active',
    ];

    protected $casts = [
        'languages' => 'array',
        'is_active' => 'boolean',
    ];

    public function pages()
    {
        return $this->hasMany(Page::class);
    }

    public function channableData()
    {
        return $this->hasMany(ChannableData::class);
    }

    public function integrations()
    {
        return $this->hasMany(SiteIntegration::class);
    }

    public function integration(string $platform): ?SiteIntegration
    {
        return $this->integrations->firstWhere('platform', $platform);
    }
}
