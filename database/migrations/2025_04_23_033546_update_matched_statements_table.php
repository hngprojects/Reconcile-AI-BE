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
        Schema::table('matched_statements', function(Blueprint $table){
            $table->enum('matched_by', ['AI', 'manual']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matched_statements', function(Blueprint $table){
            $table->dropColumn('matched_by');
        });
    }
};
