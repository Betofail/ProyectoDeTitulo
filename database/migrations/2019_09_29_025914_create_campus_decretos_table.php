<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCampusDecretosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('campus_decretos', function (Blueprint $table) {
            $table->string('codigo_campus');
            $table->date('semana_inicio');
            $table->integer('duracion');
            $table->string('nombre');
            $table->unsignedBigInteger('idAsignatura');
            $table->integer('rotacion');

            $table->primary('codigo_campus');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campus_decretos');
    }
}
