<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLedgerEntriesTable extends Migration
{
    public function up()
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('ledger_id')->constrained('bookkeeping_ledgers')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('account_category')->nullable();
            $table->enum('transaction_type', ['income', 'expense']);
            $table->date('date');
            $table->text('description');
            $table->decimal('amount', 15, 2);
            $table->boolean('paid_status')->default(false);
            $table->foreignUuid('bank_account_id')->nullable()->constrained()->onDelete('set null');
            $table->boolean('reconciled')->default(false);
            $table->string('bank_ref')->nullable();
            $table->string('invoice_or_ref_number')->nullable();
            $table->string('attachment')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ledger_entries');
    }
}