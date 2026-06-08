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
        Schema::create('page_metrics_ga4', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->integer('sessions')->default(0);
            $table->integer('pageviews')->default(0);
            $table->integer('users')->default(0);
            $table->integer('new_users')->default(0);
            $table->decimal('bounce_rate', 8, 4)->nullable();
            $table->decimal('avg_session_duration', 10, 2)->nullable();
            $table->integer('conversions')->default(0);
            $table->decimal('revenue', 12, 2)->default(0);
            $table->string('channel_group')->nullable();
            $table->timestamps();

            $table->index(['page_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_metrics_ga4');
    }
};
