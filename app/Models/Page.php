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

    public function geoMetrics()
    {
        return $this->hasMany(PageMetricGeo::class);
    }

    public function latestGeoMetric()
    {
        return $this->hasOne(PageMetricGeo::class)->latestOfMany('scored_at');
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
     * Composite priority score (0-100): margin + organic gap + CTR gap + CWV issue + GEO gap.
     * Higher = more urgent/opportunistic to work on.
     */
    public function getPriorityScoreAttribute(): int
    {
        $score = 0;

        // Marge-potentieel: max 30 pts
        $channable = $this->channableData;
        if ($channable && $channable->margin !== null) {
            $score += min(30, (float) $channable->margin * 100);
        }

        // Organisch potentieel via GSC: max 25 pts
        $gsc = $this->latestGscMetric;
        if ($gsc) {
            if ($gsc->position >= 4 && $gsc->position <= 15)      $score += 25;
            elseif ($gsc->position > 15 && $gsc->position <= 30)  $score += 12;

            // CTR-gap: max 15 pts
            $expectedCtr = $gsc->position <= 3 ? 0.10 : 0.03;
            if ($gsc->ctr !== null && $gsc->ctr < $expectedCtr) {
                $score += 15;
            }
        }

        // CWV-probleem: max 15 pts
        $cwv = $this->latestCwvMetric;
        if ($cwv && $cwv->performance_score !== null) {
            if ($cwv->performance_score < 50)     $score += 15;
            elseif ($cwv->performance_score < 90) $score += 8;
        }

        // GEO-gap: lage GEO-score = hoge verbeterkans: max 15 pts
        $geo = $this->latestGeoMetric;
        if ($geo) {
            if ($geo->geo_score < 45)     $score += 15;
            elseif ($geo->geo_score < 75) $score += 8;
        }

        return min(100, (int) $score);
    }
}
