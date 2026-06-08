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
        Schema::table('site_integrations', function (Blueprint $table) {
            $table->enum('sync_schedule', ['manual', 'daily', 'weekly'])->default('manual')->after('status');
            $table->integer('records_new')->default(0)->after('sync_schedule');
            $table->integer('records_updated')->default(0)->after('records_new');
        });
    }

    public function down(): void
    {
        Schema::table('site_integrations', function (Blueprint $table) {
            $table->dropColumn(['sync_schedule', 'records_new', 'records_updated']);
        });
    }
};
