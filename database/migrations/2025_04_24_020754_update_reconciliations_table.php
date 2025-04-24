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
        Schema::table('reconciliations', function (Blueprint $table) {
            $table->string('duration')->nullable();
            $table->enum('step', [1, 2, 3, 4, 5, 6, 7, 8, 9])->default(1);
            $table->enum('status', ['draft', 'in-progress', 'completed', 'failed'])->default('in-progress');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reconciliations', function (Blueprint $table) {
            $table->dropColumn('duration');
            $table->dropColumn('step');
            $table->dropColumn('status');
        });
    }
};
