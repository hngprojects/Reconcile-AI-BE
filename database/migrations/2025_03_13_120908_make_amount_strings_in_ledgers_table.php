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
        Schema::table('ledgers', function (Blueprint $table) {
            $table->string('amount', 100)->change();
        });
        Schema::table('statements', function (Blueprint $table) {
            $table->string('amount', 100)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ledgers', function (Blueprint $table) {
            $table->integer('amount');
        });
        Schema::table('statements', function (Blueprint $table) {
            $table->integer('amount');
        });
    }
};
