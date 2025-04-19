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
        Schema::create('ledger_payments', function(Blueprint $table){
            $table->uuid('id')->primary();
            $table->string('payment_status');
            $table->dateTime('due_date');
            $table->integer('amount_paid');
            $table->foreignUuid('bank_account_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('account_chart_id')->constrained()->cascadeOnDelete();
            $table->integer('reference')->nullable($value = true);
            $table->string('attachment')->nullable($value = true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledger_payments');
    }
};
