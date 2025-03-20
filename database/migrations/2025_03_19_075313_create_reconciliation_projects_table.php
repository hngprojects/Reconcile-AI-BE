<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReconciliationProjectsTable extends Migration
{
    public function up()
    {
        Schema::create('reconciliation_projects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('pending');
            $table->integer('progress')->default(0);
            $table->string('statement_file');
            $table->string('ledger_file');
            $table->string('ai_option');
            $table->json('result')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reconciliation_projects');
    }
}
