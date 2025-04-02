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
        Schema::table('statements', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->longText('other_information');
            $table->string('person');
        });

        Schema::table('ledgers', function (Blueprint $table) {
            $table->dropColumn('description');
            $table->longText('other_information');
            $table->string('person');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('statements', function (Blueprint $table) {
            $table->longText('description');
            $table->dropColumn('other_information');
            $table->dropColumn('person');
        });

        Schema::table('ledgers', function (Blueprint $table) {
            $table->longText('description');
            $table->dropColumn('other_information');
            $table->dropColumn('person');
        });
    }
};
