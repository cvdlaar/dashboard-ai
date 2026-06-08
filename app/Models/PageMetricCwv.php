<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageMetricCwv extends Model
{
    protected $table = 'page_metrics_cwv';

    protected $fillable = [
        'page_id', 'strategy', 'performance_score', 'seo_score', 'accessibility_score',
        'best_practices_score', 'lcp', 'cls', 'inp', 'fcp', 'ttfb', 'tbt', 'speed_index',
        'lcp_rating', 'cls_rating', 'inp_rating',
    ];

    public function page()
    {
        return $this->belongsTo(Page::class);
    }
}
