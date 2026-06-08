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
        Schema::create('channable_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->foreignId('page_id')->nullable()->constrained()->nullOnDelete();
            $table->string('url', 1000);
            $table->string('sku')->nullable();
            $table->string('title');
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('sale_price', 12, 2)->nullable();
            $table->decimal('margin', 8, 4)->nullable();
            $table->decimal('margin_amount', 12, 2)->nullable();
            $table->string('brand')->nullable();
            $table->string('category')->nullable();
            $table->string('availability')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('feed_updated_at')->nullable();
            $table->timestamps();

            $table->index(['site_id', 'url']);
            $table->index(['site_id', 'sku']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('channable_data');
    }
};
