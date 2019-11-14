<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAlumnoSeccionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('alumno_seccions', function (Blueprint $table) {
            $table->bigIncrements('idSeccion');
            $table->string('rut_alumno')->unique();
            $table->unsignedBigInteger('nrc');
            $table->string('resp_encuesta');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('alumno_seccions');
    }
}
