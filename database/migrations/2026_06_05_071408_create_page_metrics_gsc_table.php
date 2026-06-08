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
        Schema::create('page_metrics_gsc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('query', 500)->nullable();
            $table->decimal('position', 8, 2)->nullable();
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->decimal('ctr', 8, 4)->nullable();
            $table->string('device')->nullable();
            $table->string('country', 5)->nullable();
            $table->timestamps();

            $table->index(['page_id', 'date']);
            $table->index(['page_id', 'query']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_metrics_gsc');
    }
};
