<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStudentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('students', function (Blueprint $table) {
            $table->foreignId('id_discipline');
            $table->string('id_group',12);
            $table->unsignedInteger('edu_year');
            $table->unsignedInteger('semestry');
            $table->primary(['id_discipline','id_group']);
            $table->foreign('id_group')->references('id_group')->on('groups');
            $table->foreign('id_discipline')->references('id_discipline')->on('disciplines');


            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('students');
    }
}
