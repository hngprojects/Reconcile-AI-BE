<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Skip vector extension in testing environment
        if (app()->environment('testing')) {
            return;
        }
        
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Skip vector extension in testing environment
        if (app()->environment('testing')) {
            return;
        }
        
        DB::statement('DROP EXTENSION vector');
    }
};
