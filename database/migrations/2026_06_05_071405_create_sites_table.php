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
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain')->unique();
            $table->string('sitemap_url')->nullable();
            $table->string('channable_feed_url')->nullable();
            $table->json('languages')->default('["nl"]');
            $table->string('ga4_property_id')->nullable();
            $table->string('gsc_site_url')->nullable();
            $table->string('google_ads_customer_id')->nullable();
            $table->string('bing_ads_account_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
