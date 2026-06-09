<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteGeoCheck extends Model
{
    protected $table = 'site_geo_checks';

    protected $fillable = [
        'site_id', 'checked_at', 'robots_txt', 'has_llms_txt',
        'llm_bots', 'reddit_url', 'wikipedia_url', 'youtube_url', 'wikidata_id',
    ];

    protected $casts = [
        'checked_at'   => 'datetime',
        'has_llms_txt' => 'boolean',
        'llm_bots'     => 'array',
    ];

    // Bekende LLM-crawlers en hun gewicht/impact
    public static array $knownBots = [
        'GPTBot'          => ['label' => 'ChatGPT (OpenAI)',     'impact' => 'high'],
        'ChatGPT-User'    => ['label' => 'ChatGPT browsing',     'impact' => 'high'],
        'anthropic-ai'    => ['label' => 'Claude (Anthropic)',   'impact' => 'high'],
        'ClaudeBot'       => ['label' => 'Claude crawler',       'impact' => 'high'],
        'PerplexityBot'   => ['label' => 'Perplexity AI',        'impact' => 'high'],
        'Google-Extended' => ['label' => 'Google Gemini',        'impact' => 'high'],
        'Bytespider'      => ['label' => 'ByteDance / TikTok',   'impact' => 'medium'],
        'CCBot'           => ['label' => 'CommonCrawl',          'impact' => 'medium'],
        'cohere-ai'       => ['label' => 'Cohere AI',            'impact' => 'low'],
        'Amazonbot'       => ['label' => 'Amazon Alexa',         'impact' => 'low'],
    ];

    public function site()
    {
        return $this->belongsTo(Site::class);
    }

    public function getBotStatus(string $bot): string
    {
        return ($this->llm_bots[$bot] ?? 'unknown');
    }
}
