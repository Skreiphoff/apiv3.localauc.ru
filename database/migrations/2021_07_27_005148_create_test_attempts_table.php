<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTestAttemptsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('test_attempts', function (Blueprint $table) {
            $table->id('id_attempt');
            $table->foreignId('id_test');
            $table->foreignId('id_user');
            $table->integer('attempt_number')->unsigned()->default(1);
            $table->string('status',70)->default('ATTEMPT_CREATED');
            $table->decimal('progress',5,2,true)->default(0);
            $table->integer('mark')->unsigned()->nullable();
            $table->string('fail_reason',70)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('id_test')->references('id_test')->on('tests');
            $table->foreign('id_user')->references('id_user')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('test_attempts');
    }
}
