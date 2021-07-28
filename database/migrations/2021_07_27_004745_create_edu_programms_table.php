<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEduProgrammsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('edu_programs', function (Blueprint $table) {
            $table->id('id_element');
            $table->foreignId('id_discipline');
            $table->integer('order')->unsigned();
            $table->boolean('required')->default(0);
            $table->string('instance');
            $table->foreignId('instance_element_id');
            $table->integer('time')->unsigned()->nullable();
            $table->foreignId('deleted_by')->nullable();
            $table->softDeletes();

            $table->foreign('id_discipline')->references('id_discipline')->on('disciplines');
            $table->foreign('deleted_by')->references('id_user')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('edu_programms');
    }
}
