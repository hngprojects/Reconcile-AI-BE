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
        Schema::table('account_chart_categories', function (Blueprint $table) {

            $table->boolean('is_required')->after('description')->default(false);
            $table->boolean('is_active')->after('is_required')->default(false);
        });
    }

    /**I wan
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('account_chart_categories', function (Blueprint $table) {
            $table->dropColumn(['is_required', 'is_active']);
        });
    }
};
