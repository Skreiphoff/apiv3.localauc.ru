<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id('id_user');
            $table->string('id_group',12)->nullable();
            $table->string('login',50)->unique();
            $table->string('email',50)->unique()->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('banned')->default(0);
            $table->string('password');
            $table->string('s_name',30)->nullable();
            $table->string('f_name',30)->nullable();
            $table->string('fth_name',30)->nullable();
            $table->string('photo',255)->nullable();
            $table->tinyInteger('role',false,true)->default(1);
            $table->timestamp('last_login')->nullable();
            $table->boolean('notify_via_email')->default(0);
            $table->boolean('notify_via_app')->default(1);
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('id_group')->references('id_group')->on('groups');

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
