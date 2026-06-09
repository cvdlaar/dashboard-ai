<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('page_metrics_geo', function (Blueprint $table) {
            $table->boolean('has_h1')->default(false)->after('has_meta_description');
            $table->unsignedSmallInteger('h1_count')->default(0)->after('has_h1');
            $table->boolean('has_organization_schema')->default(false)->after('h1_count');
            $table->boolean('has_author_schema')->default(false)->after('has_organization_schema');
            $table->boolean('has_table_of_contents')->default(false)->after('has_author_schema');
            $table->boolean('has_list_content')->default(false)->after('has_table_of_contents');
            $table->unsignedSmallInteger('external_link_count')->default(0)->after('has_list_content');
        });
    }

    public function down(): void
    {
        Schema::table('page_metrics_geo', function (Blueprint $table) {
            $table->dropColumn([
                'has_h1', 'h1_count', 'has_organization_schema', 'has_author_schema',
                'has_table_of_contents', 'has_list_content', 'external_link_count',
            ]);
        });
    }
};
