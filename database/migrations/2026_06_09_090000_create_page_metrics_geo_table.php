<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_metrics_geo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->timestamp('scored_at');

            // 1. Broncode / structured data
            $table->boolean('has_json_ld')->default(false);
            $table->json('json_ld_types')->nullable();       // ['Product','BreadcrumbList',...]
            $table->boolean('has_faq_schema')->default(false);
            $table->boolean('has_product_schema')->default(false);
            $table->boolean('has_breadcrumb_schema')->default(false);
            $table->boolean('has_open_graph')->default(false);
            $table->boolean('has_canonical')->default(false);
            $table->boolean('has_meta_description')->default(false);

            // 3. Content architectuur
            $table->unsignedSmallInteger('intro_word_count')->nullable();
            $table->boolean('has_citable_intro')->default(false);   // eerste alinea ≥ 35 woorden
            $table->unsignedTinyInteger('question_heading_count')->default(0);
            $table->boolean('has_faq_block')->default(false);       // FAQPage schema of <details>/<summary>
            $table->boolean('has_comparison_content')->default(false); // vergelijk/verschil/vs in copy
            $table->date('date_modified')->nullable();
            $table->unsignedSmallInteger('freshness_days')->nullable(); // dagen geleden bijgewerkt

            // Totaalscore + aandachtspunten
            $table->unsignedTinyInteger('geo_score')->default(0);   // 0-100
            $table->json('issues')->nullable();                     // ['Geen JSON-LD', ...]

            $table->timestamps();

            $table->index('page_id');
        });

        Schema::create('site_geo_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->timestamp('checked_at');

            // LLM-robots
            $table->text('robots_txt')->nullable();
            $table->boolean('has_llms_txt')->default(false);
            $table->json('llm_bots')->nullable(); // {GPTBot: 'allowed', ClaudeBot: 'blocked', ...}

            // Mention share (handmatig ingesteld)
            $table->string('reddit_url')->nullable();
            $table->string('wikipedia_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('wikidata_id')->nullable();       // Q-nummer bijv. Q12345

            $table->timestamps();

            $table->index('site_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_geo_checks');
        Schema::dropIfExists('page_metrics_geo');
    }
};
