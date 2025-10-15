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
        // Skip vector columns in testing environment
        if (app()->environment('testing')) {
            Schema::table('statements', function (Blueprint $table) {
                $table->text('embedding')->nullable();
            });

            Schema::table('ledgers', function (Blueprint $table) {
                $table->text('embedding')->nullable();
            });
            return;
        }
        
        Schema::table('statements', function (Blueprint $table) {
            $table->vector('embedding', 1536);
        });

        Schema::table('ledgers', function (Blueprint $table) {
            $table->vector('embedding', 1536);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('statements', function (Blueprint $table) {
            $table->dropColumn('embedding');
        });

        Schema::table('ledgers', function (Blueprint $table) {
            $table->dropColumn('embedding');
        });
    }
};
