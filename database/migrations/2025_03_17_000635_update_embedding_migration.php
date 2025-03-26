<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class  extends Migration
{
    public function up()
    {
        Schema::table('statements', function (Blueprint $table) {
            $table->vector('embedding', 768)->nullable($value = true)->change();
        });

        Schema::table('ledgers', function (Blueprint $table) {
            $table->vector('embedding', 768)->nullable($value = true)->change();
        });
    }

    public function down()
    {
        Schema::table('statements', function (Blueprint $table) {
            $table->vector('embedding', 1536)->change();
        });

        Schema::table('ledgers', function (Blueprint $table) {
            $table->vector('embedding', 1536)->change();
        });
    }
};
