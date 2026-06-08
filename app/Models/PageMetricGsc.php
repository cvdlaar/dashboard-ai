<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageMetricGsc extends Model
{
    protected $table = 'page_metrics_gsc';

    protected $fillable = [
        'page_id', 'date', 'query', 'position', 'impressions', 'clicks', 'ctr', 'device', 'country',
    ];

    protected $casts = ['date' => 'date'];

    public function page()
    {
        return $this->belongsTo(Page::class);
    }
}
