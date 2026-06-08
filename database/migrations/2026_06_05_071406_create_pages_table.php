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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('url', 1000);
            $table->string('path', 1000);
            $table->enum('type', ['product', 'category', 'content', 'other'])->default('other');
            $table->string('language', 5)->default('nl');
            $table->string('title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->string('h1')->nullable();
            $table->string('category_slug')->nullable();
            $table->foreignId('category_owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('has_json_ld')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_crawled_at')->nullable();
            $table->timestamps();

            $table->unique(['site_id', 'url']);
            $table->index(['site_id', 'type']);
            $table->index(['site_id', 'category_slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
