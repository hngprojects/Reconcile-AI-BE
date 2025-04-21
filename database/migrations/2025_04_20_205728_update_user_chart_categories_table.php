<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // In your migration file
    public function up()
    {
        Schema::table('user_chart_categories', function (Blueprint $table) {
            // Remove the existing primary key constraint first
            $table->dropPrimary(['id']);

            // Drop the UUID id column
            $table->dropColumn('id');

            // Add composite primary key
            $table->primary(['user_id', 'account_chart_category_id']);
        });
    }

    public function down()
    {
        Schema::table('user_chart_categories', function (Blueprint $table) {
            // Remove the composite primary key first
            $table->dropPrimary(['user_id', 'account_chart_category_id']);

            // Add back the UUID id column
            $table->uuid('id')->primary();

            // You may need to repopulate this column with UUIDs
            // This would require raw DB statements in a separate migration
        });
    }
};
