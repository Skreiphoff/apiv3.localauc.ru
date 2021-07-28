<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestVariantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('test_variants', function (Blueprint $table) {
            $table->foreignId('id_attempt');
            $table->foreignId('id_question');
            $table->string('answer',255)->nullable();
            $table->boolean('is_correct')->nullable();
            $table->json('details');
            $table->integer('order')->unsigned();
            $table->integer('time_spent')->unsigned()->default(0);

            $table->foreign('id_attempt')->references('id_attempt')->on('test_attempts');
            $table->foreign('id_question')->references('id_question')->on('test_questions');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('test_variants');
    }
}
