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
        Schema::create('page_metrics_ads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->enum('platform', ['google', 'bing'])->default('google');
            $table->string('campaign_name')->nullable();
            $table->string('ad_group_name')->nullable();
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->decimal('ctr', 8, 4)->nullable();
            $table->decimal('cost', 12, 2)->default(0);
            $table->decimal('avg_cpc', 10, 4)->nullable();
            $table->integer('conversions')->default(0);
            $table->decimal('conversion_value', 12, 2)->default(0);
            $table->decimal('roas', 8, 4)->nullable();
            $table->timestamps();

            $table->index(['page_id', 'date', 'platform']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_metrics_ads');
    }
};
