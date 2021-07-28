<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompleteLabsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('complete_labs', function (Blueprint $table) {
            $table->foreignId('id_lab');
            $table->foreignId('id_student');
            $table->foreignId('id_teacher')->nullable();
            $table->dateTime('complete_date');
            $table->unsignedSmallInteger('mark')->nullable();
            $table->string('file',255);
            $table->unsignedSmallInteger('status');
            $table->text('comments');
            $table->timestamps();

            $table->primary(['id_lab','id_student']);
            $table->foreign('id_lab')->references('id_lab')->on('labs');
            $table->foreign('id_student')->references('id_user')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('complete_labs');
    }
}
