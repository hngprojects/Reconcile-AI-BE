<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStatementRecordsTable extends Migration
{
    public function up()
    {
        Schema::create('statement_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('reconciliation_project_id');
            $table->foreign('reconciliation_project_id')
                  ->references('id')
                  ->on('reconciliation_projects')
                  ->onDelete('cascade');
            $table->date('date')->nullable();
            $table->string('name_of_person')->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->text('other_information')->nullable();
            $table->vector('vector_structured', 768)->nullable();
            $table->vector('vector_full', 768)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('statement_records');
    }
}
