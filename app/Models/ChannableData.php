<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChannableData extends Model
{
    protected $table = 'channable_data';

    protected $fillable = [
        'site_id', 'page_id', 'url', 'sku', 'title', 'price', 'sale_price',
        'margin', 'margin_amount', 'brand', 'category', 'availability',
        'is_active', 'feed_updated_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'feed_updated_at' => 'datetime',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function page()
    {
        return $this->belongsTo(Page::class);
    }
}
