<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    protected $fillable = [
        'site_id', 'product_group_id', 'url', 'path', 'type', 'language', 'title',
        'meta_description', 'h1', 'category_slug', 'category_owner_id',
        'has_json_ld', 'is_active', 'last_crawled_at',
    ];

    protected $casts = [
        'has_json_ld' => 'boolean',
        'is_active' => 'boolean',
        'last_crawled_at' => 'datetime',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function categoryOwner()
    {
        return $this->belongsTo(\App\Models\User::class, 'category_owner_id');
    }

    public function productGroup()
    {
        return $this->belongsTo(ProductGroup::class);
    }

    public function gscMetrics()
    {
        return $this->hasMany(PageMetricGsc::class);
    }

    public function latestGscMetric()
    {
        return $this->hasOne(PageMetricGsc::class)->latestOfMany('date')->whereNull('query');
    }

    public function cwvMetrics()
    {
        return $this->hasMany(PageMetricCwv::class);
    }

    public function latestCwvMetric()
    {
        return $this->hasOne(PageMetricCwv::class)->latestOfMany()->where('strategy', 'mobile');
    }

    public function ga4Metrics()
    {
        return $this->hasMany(PageMetricGa4::class);
    }

    public function adsMetrics()
    {
        return $this->hasMany(PageMetricAds::class);
    }

    public function channableData()
    {
        return $this->hasOne(ChannableData::class);
    }

    /**
     * Composite priority score (0-100) based on margin, organic potential, CWV, CTR gap.
     * Higher = more urgent to work on.
     */
    public function getPriorityScoreAttribute(): int
    {
        $score = 0;

        $channable = $this->channableData;
        if ($channable && $channable->margin !== null) {
            $score += min(40, (float) $channable->margin * 100);
        }

        $gsc = $this->latestGscMetric;
        if ($gsc) {
            // Positions 4-15 = high organic opportunity
            if ($gsc->position >= 4 && $gsc->position <= 15) {
                $score += 30;
            } elseif ($gsc->position > 15 && $gsc->position <= 30) {
                $score += 15;
            }

            // Low CTR vs expected = title/meta opportunity
            $expectedCtr = $gsc->position <= 3 ? 0.10 : 0.03;
            if ($gsc->ctr !== null && $gsc->ctr < $expectedCtr) {
                $score += 15;
            }
        }

        $cwv = $this->latestCwvMetric;
        if ($cwv && $cwv->performance_score !== null && $cwv->performance_score < 50) {
            $score += 15;
        } elseif ($cwv && $cwv->performance_score !== null && $cwv->performance_score < 90) {
            $score += 8;
        }

        return min(100, (int) $score);
    }
}
