<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAsignaturasTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::connection('mysql3')->create('asignaturas', function (Blueprint $table) {
            $table->string('idAsignatura')->primary();
            $table->string('codigo_asignatura');
            $table->string('nombre');
            $table->string('idCarrera');
            $table->string('semestre');
            $table->string('sede');
            $table->tinyInteger('confirmacion_semestre');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('asignaturas');
    }
}
