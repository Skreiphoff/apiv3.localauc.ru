<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserEduProgressTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_edu_progress', function (Blueprint $table) {
            $table->foreignId('id_user');
            $table->foreignId('id_element');
            $table->boolean('completed')->default(0);
            $table->integer('time_spent')->default(0);
            $table->timestamps();

            $table->foreign('id_user')->references('id_user')->on('users');
            $table->foreign('id_element')->references('id_element')->on('edu_programs');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_edu_progress');
    }
}
