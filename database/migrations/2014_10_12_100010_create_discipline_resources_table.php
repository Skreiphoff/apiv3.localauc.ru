<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDisciplineResourcesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('discipline_resources', function (Blueprint $table) {
            $table->foreignId('id_discipline');
            $table->foreignId('id_resource');
            $table->primary(['id_discipline','id_resource']);
            $table->foreign('id_discipline')->references('id_discipline')->on('disciplines');
            $table->foreign('id_resource')->references('id_resource')->on('resources');
        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('discipline_resources');
    }
}
