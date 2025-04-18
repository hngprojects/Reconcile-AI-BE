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
        Schema::create('statement_files', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('period');
            $table->foreignUuid('user_file_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('bank_account_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::dropIfExists('reconciliation_files');

        Schema::create('reconciliation_statement_files', function (Blueprint $table) {
            $table->foreignUuid('reconciliation_id')->references('id')->on('reconciliations')->constrained()->onDelete('cascade');
            $table->foreignUuid('statement_file_id')->references('id')->on('statement_files')->constrained()->onDelete('cascade');
            $table->primary(['reconciliation_id', 'statement_file_id']);
            $table->timestamps();
        });

        Schema::create('reconciliation_ledgers', function (Blueprint $table) {
            $table->foreignUuid('reconciliation_id')->references('id')->on('reconciliations')->constrained()->onDelete('cascade');
            $table->foreignUuid('bookkeeping_ledger_id')->references('id')->on('bookkeeping_ledgers')->constrained()->onDelete('cascade');
            $table->primary(['reconciliation_id', 'bookkeeping_ledger_id']);
            $table->timestamps();
        });

        Schema::table('reconciliations', function (Blueprint $table) {
            $table->dropColumn('option');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statement_files');
        Schema::dropIfExists('reconciliation_statement_file');
        Schema::dropIfExists('ledger_reconciliation');
    }
};
