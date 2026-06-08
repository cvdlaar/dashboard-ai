<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageMetricAds extends Model
{
    protected $table = 'page_metrics_ads';

    protected $fillable = [
        'page_id', 'date', 'platform', 'campaign_name', 'ad_group_name',
        'impressions', 'clicks', 'ctr', 'cost', 'avg_cpc',
        'conversions', 'conversion_value', 'roas',
    ];

    protected $casts = ['date' => 'date'];

    public function page()
    {
        return $this->belongsTo(Page::class);
    }
}
