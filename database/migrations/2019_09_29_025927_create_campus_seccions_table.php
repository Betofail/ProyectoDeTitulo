<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCampusSeccionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql3')->create('campus_seccions', function (Blueprint $table) {
            $table->bigIncrements('idCampus_clinico');
            $table->unsignedBigInteger('alumno_seccion');
            $table->unsignedBigInteger('rotacion');
            $table->unsignedBigInteger('seccion_semestre');
            $table->string('link_encuesta');
            $table->string('profesor_seccion');
            $table->integer('nrc');
            $table->date('fecha_inicio');
            $table->date('fecha_termino');
            $table->string('res_encuesta');
            $table->string('entrega_rubrica');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('campus_seccions');
    }
}
