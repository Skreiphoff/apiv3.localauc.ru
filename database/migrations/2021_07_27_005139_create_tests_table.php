<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tests', function (Blueprint $table) {
            $table->id('id_test');
            $table->foreignId('id_discipline');
            $table->string('name',255);
            $table->integer('max_attempts')->nullable();
            $table->integer('time')->nullable();
            $table->decimal('pass_weight',5,2,true);
            $table->json('parameters');
            $table->timestamps();

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
        Schema::dropIfExists('tests');
    }
}
