<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCourseThemesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('course_themes', function (Blueprint $table) {
            $table->id('id_theme');
            $table->foreignId('id_student')->nullable();
            $table->foreignId('id_discipline');
            $table->string('description',255);
            $table->boolean('confirmed');


            $table->foreign('id_student')->references('id_user')->on('users');
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
        Schema::dropIfExists('course_themes');
    }
}
