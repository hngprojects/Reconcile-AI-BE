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
        Schema::create('plans', function (Blueprint $table) {
            $table->uuid('id')->primary(); // UUID as primary key
            $table->string('name')->index(); // Indexed for faster search
            $table->text('description')->nullable();
            $table->integer('plan_length'); // Length of plan in days or months
            $table->string('plan')->index(); // Indexed for search optimization
            $table->integer('reconciliations_per_month')->default(0); // Reconciliation limits
            $table->decimal('amount', 10, 2)->default(0.00); // Plan amount
            // $table->integer('expiration_days')->nullable(); // Expiration days for Basic plan
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
