<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLabConfigsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lab_configs', function (Blueprint $table) {
            $table->foreignId('id_lab');
            $table->string('id_group',12);
            $table->dateTime('deadline')->nullable();
            $table->dateTime('allowed_after')->default('CURRENT_TIMESTAMP');
            $table->primary(['id_lab','id_group']);
            $table->foreign('id_lab')->references('id_lab')->on('labs');
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
        Schema::dropIfExists('lab_configs');
    }
}
