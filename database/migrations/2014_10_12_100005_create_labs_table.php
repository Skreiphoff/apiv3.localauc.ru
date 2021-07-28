<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLabsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('labs', function (Blueprint $table) {
            $table->id('id_lab');
            $table->foreignId('id_discipline');
            $table->foreignId('id_form');
            $table->string('description', 100);
            $table->string('file',255)->nullable();
            $table->text('comment')->nullable();
            $table->timestamps();

            $table->foreign('id_discipline')->references('id_discipline')->on('disciplines');
            $table->foreign('id_form')->references('id_form')->on('lab_forms');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('labs');
    }
}
