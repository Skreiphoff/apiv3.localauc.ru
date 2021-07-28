<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCourseDisciplinesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('course_disciplines', function (Blueprint $table) {
//            $table->id();
            $table->foreignId('id_course');
            $table->foreignId('id_discipline');
            $table->integer('order');
            $table->boolean('required')->default(0);

            $table->foreign('id_course')->references('id_course')->on('courses');
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
        Schema::dropIfExists('course_disciplines');
    }
}
