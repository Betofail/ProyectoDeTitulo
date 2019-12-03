<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Malla extends Model
{
    protected $connection = 'mysql3';
    public $timestamps = false;
    protected $fillable = ['CodAsign','Nombre','CodCarrera','Encuesta','PeriodoCatalogo','Vigente'];
}
