<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestQuestionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('test_questions', function (Blueprint $table) {
            $table->id('id_question');
            $table->foreignId('id_test');
            $table->foreignId('id_type');
            $table->text('text');
            $table->json('answers');
            $table->boolean('required')->default(0);
            $table->integer('time')->unsigned()->nullable();
            $table->integer('level')->default(1);
            $table->decimal('weight',3,2,true)->default(1);

            $table->foreign('id_test')->references('id_test')->on('tests');
            $table->foreign('id_type')->references('id_type')->on('test_question_types');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('test_questions');
    }
}
