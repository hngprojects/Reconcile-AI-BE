<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ledgers', function (Blueprint $table) {
            $table->string('transaction_type')->change();
        });

        DB::statement('ALTER TABLE ledgers DROP CONSTRAINT IF EXISTS ledgers_transaction_type_check');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ledgers', function (Blueprint $table) {
            $table->enum('transaction_type', ['Expense', 'Income', 'Payable', 'Receivable'])->default('Expense');
            $table->foreignUuid('bookkeeping_ledger_id')->constrained()->cascadeOnDelete();
        });
    }
};
