<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestFailuresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('test_failures', function (Blueprint $table) {
            $table->id('id_failure');
            $table->foreignId('id_attempt');
            $table->string('reason',70);
            $table->boolean('is_fatal')->default(0);
            $table->timestamps();

            $table->foreign('id_attempt')->references('id_attempt')->on('test_attempts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('test_failures');
    }
}
