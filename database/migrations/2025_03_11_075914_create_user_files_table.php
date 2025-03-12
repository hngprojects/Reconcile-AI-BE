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
        Schema::create('user_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('file_name');
            $table->foreignId('user_id');
            $table->string('type');
            $table->timestamps();
        });

        Schema::create('reconciliation_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('reconciliation_id');
            $table->foreignUuid('file_id');
            $table->timestamps();
        });

        Schema::table('reconciliations', function (Blueprint $table) {
            $table->dropColumn('ledger_file');
            $table->dropColumn('statement_file');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_files');
        Schema::dropIfExists('reconciliation_files');
        Schema::table('reconciliations', function(Blueprint $table) {
            $table->string('ledger_file');
            $table->string('statement_file');
        });
    }
};
