<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageMetricGeo extends Model
{
    protected $table = 'page_metrics_geo';

    protected $fillable = [
        'page_id', 'scored_at',
        'has_json_ld', 'json_ld_types', 'has_faq_schema', 'has_product_schema',
        'has_breadcrumb_schema', 'has_organization_schema', 'has_author_schema',
        'has_open_graph', 'has_canonical', 'has_meta_description',
        'has_h1', 'h1_count', 'has_table_of_contents', 'has_list_content',
        'intro_word_count', 'has_citable_intro', 'question_heading_count',
        'has_faq_block', 'has_comparison_content', 'external_link_count',
        'date_modified', 'freshness_days',
        'geo_score', 'issues',
    ];

    protected $casts = [
        'scored_at'               => 'datetime',
        'date_modified'           => 'date',
        'has_json_ld'             => 'boolean',
        'has_faq_schema'          => 'boolean',
        'has_product_schema'      => 'boolean',
        'has_breadcrumb_schema'   => 'boolean',
        'has_organization_schema' => 'boolean',
        'has_author_schema'       => 'boolean',
        'has_open_graph'          => 'boolean',
        'has_canonical'           => 'boolean',
        'has_meta_description'    => 'boolean',
        'has_h1'                  => 'boolean',
        'has_table_of_contents'   => 'boolean',
        'has_list_content'        => 'boolean',
        'has_citable_intro'       => 'boolean',
        'has_faq_block'           => 'boolean',
        'has_comparison_content'  => 'boolean',
        'json_ld_types'           => 'array',
        'issues'                  => 'array',
    ];

    public function page()
    {
        return $this->belongsTo(Page::class);
    }

    public function getScoreLabelAttribute(): string
    {
        return match(true) {
            $this->geo_score >= 75 => 'Goed',
            $this->geo_score >= 45 => 'Verbetering mogelijk',
            default                => 'Aandacht nodig',
        };
    }

    public function getScoreColorAttribute(): string
    {
        return match(true) {
            $this->geo_score >= 75 => 'good',
            $this->geo_score >= 45 => 'warning',
            default                => 'poor',
        };
    }
}
