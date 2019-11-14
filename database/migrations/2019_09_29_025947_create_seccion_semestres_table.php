<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSeccionSemestresTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('seccion_semestres', function (Blueprint $table){
            $table->bigIncrements('idSeccion');
            $table->unsignedBigInteger('idAsignatura');
            $table->unsignedBigInteger('idPeriodo');
            $table->string('idDocente');
            $table->string('link_encuesta');
            $table->integer('nrc');
            $table->date('fecha_inicio_encuesta');
            $table->date('fecha_termino_encuesta');
            $table->string('actividad');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('seccion_semestres');
    }
}
