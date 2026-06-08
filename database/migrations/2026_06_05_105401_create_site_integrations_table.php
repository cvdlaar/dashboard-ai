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
        Schema::create('site_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->enum('platform', [
                'google_search_console',
                'google_analytics',
                'google_ads',
                'bing_ads',
                'channable',
                'pagespeed',
            ]);
            $table->enum('status', ['connected', 'disconnected', 'error'])->default('disconnected');
            $table->text('credentials')->nullable();  // encrypted JSON
            $table->json('settings')->nullable();     // platform-specific config (property ID etc.)
            $table->string('error_message')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamps();

            $table->unique(['site_id', 'platform']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_integrations');
    }
};
