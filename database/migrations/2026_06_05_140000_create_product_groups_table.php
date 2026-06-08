<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['site_id', 'slug']);
        });

        // Specialisten koppelen aan productgroepen
        Schema::create('product_group_user', function (Blueprint $table) {
            $table->foreignId('product_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['product_group_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_group_user');
        Schema::dropIfExists('product_groups');
    }
};
