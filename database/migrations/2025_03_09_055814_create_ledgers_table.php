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
        Schema::create('ledgers', function (Blueprint $table) {
            $table->uuid('id');
            $table->string('date');
            $table->string('description');
            $table->integer('amount');
            $table->timestamps();
        });

        Schema::create('matched_ledgers_and_statements', function (Blueprint $table) {
            $table->uuid('id');
            $table->foreignUuid('user_id');
            $table->foreignUuid('ledger_id');
            $table->foreignUuid('statement_id');
            $table->foreignUuid('guest_id');
            $table->enum('status', ['Matched', 'Unmatched']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ledgers');
    }
};
