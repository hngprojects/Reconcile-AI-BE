<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up()
    {
        Schema::table('user_chart_categories', function (Blueprint $table) {
            // Remove the composite primary key
            $table->dropPrimary(['user_id', 'account_chart_category_id']);
        });

        // Add UUID column without primary key constraint first
        Schema::table('user_chart_categories', function (Blueprint $table) {
            $table->uuid('id')->first()->nullable();
        });

        // Generate UUIDs for existing records
        DB::statement('UPDATE user_chart_categories SET id = gen_random_uuid()');

        // Now make it primary and not nullable
        Schema::table('user_chart_categories', function (Blueprint $table) {
            $table->primary('id');
        });
    }

    public function down()
    {
        Schema::table('user_chart_categories', function (Blueprint $table) {
            // Remove UUID primary key
            $table->dropPrimary(['id']);
            $table->dropColumn('id');

            // Restore composite primary key
            $table->primary(['user_id', 'account_chart_category_id']);
        });
    }
};
