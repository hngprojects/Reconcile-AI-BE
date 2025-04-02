<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBookkeepingLedgersTable extends Migration
{
    public function up()
    {
        Schema::create('bookkeeping_ledgers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->jsonb('categories');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('bookkeeping_ledgers');
    }
}