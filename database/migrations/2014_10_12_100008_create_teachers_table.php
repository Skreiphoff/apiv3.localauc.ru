<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeachersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('teachers', function (Blueprint $table) {
            $table->foreignId('id_discipline');
            $table->foreignId('id_teacher');
            $table->primary(['id_discipline','id_teacher']);
            $table->foreign('id_teacher')->references('id_user')->on('users');
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
        Schema::dropIfExists('teachers');
    }
}
