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
        Schema::table('reconciled_records', function (Blueprint $table) {
            $table->dropColumn('user_id');
            $table->foreignUuid('reconciliation_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reconciled_records', function (Blueprint $table) {
            $table->foreignId('user_id');
            $table->dropColumn('reconciliation_id');
        });
    }
};
