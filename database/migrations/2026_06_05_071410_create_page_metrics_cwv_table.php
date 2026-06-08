<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('page_metrics_cwv', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->enum('strategy', ['mobile', 'desktop'])->default('mobile');
            $table->integer('performance_score')->nullable();
            $table->integer('seo_score')->nullable();
            $table->integer('accessibility_score')->nullable();
            $table->integer('best_practices_score')->nullable();
            $table->decimal('lcp', 8, 3)->nullable();
            $table->decimal('cls', 8, 4)->nullable();
            $table->decimal('inp', 8, 3)->nullable();
            $table->decimal('fcp', 8, 3)->nullable();
            $table->decimal('ttfb', 8, 3)->nullable();
            $table->decimal('tbt', 8, 3)->nullable();
            $table->decimal('speed_index', 8, 3)->nullable();
            $table->enum('lcp_rating', ['good', 'needs-improvement', 'poor'])->nullable();
            $table->enum('cls_rating', ['good', 'needs-improvement', 'poor'])->nullable();
            $table->enum('inp_rating', ['good', 'needs-improvement', 'poor'])->nullable();
            $table->timestamps();

            $table->index(['page_id', 'strategy', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_metrics_cwv');
    }
};
