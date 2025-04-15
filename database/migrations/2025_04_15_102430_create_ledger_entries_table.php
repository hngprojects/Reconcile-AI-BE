<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ledger_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('ledger_category');
            $table->enum('transaction_type', ['income', 'expense']);
            $table->timestamp('transaction_date');
            $table->text('description');
            $table->decimal('amount', 15, 2);
            $table->enum('paid_status', ['paid', 'unpaid', 'partial']);
            $table->timestamp('due_date')->nullable();
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->uuid('bank_account_id');
            $table->foreign('bank_account_id')->references('id')->on('bank_accounts')->onDelete('cascade');
            $table->string('account_category')->nullable();
            $table->string('reference')->nullable();
            $table->string('attachment')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ledger_entries');
    }
};