<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateRotacionSemestresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rotacion_semestres', function (Blueprint $table) {
            $table->integer('idRotacion');
            $table->date('fecha_inicio');
            $table->date('fecha_termino');
            $table->unsignedBigInteger('idCampus');
            $table->unsignedBigInteger('idHospital');
            $table->integer('cupos');
            $table->unsignedBigInteger('idPeriodo');
            $table->date('fecha_inicio_encuesta');
            $table->date('fecha_termino_encuesta');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('rotacion_semestres');
    }
}
