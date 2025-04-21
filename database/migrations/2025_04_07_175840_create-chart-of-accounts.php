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
        Schema::create('account_chart_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('description');
            $table->timestamps();
        });


        Schema::create('user_chart_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('account_chart_category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('account_charts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('account_chart_category_id')->constrained()->cascadeOnDelete();
            $table->integer('account_number');
            $table->string('account_name');
            $table->string('description');
            $table->integer('balance');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('account_chart_categories');
        Schema::dropIfExists('user_chart_categories');
        Schema::dropIfExists('account_charts');
    }
};
