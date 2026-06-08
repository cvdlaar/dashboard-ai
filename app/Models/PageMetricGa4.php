<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageMetricGa4 extends Model
{
    protected $table = 'page_metrics_ga4';

    protected $fillable = [
        'page_id', 'date', 'sessions', 'pageviews', 'users', 'new_users',
        'bounce_rate', 'avg_session_duration', 'conversions', 'revenue', 'channel_group',
    ];

    protected $casts = ['date' => 'date'];

    public function page()
    {
        return $this->belongsTo(Page::class);
    }
}
