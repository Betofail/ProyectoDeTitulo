<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Alumno extends Model
{
    protected $connection = 'mysql3';
    protected $fillable = ['rut','nombre','email','idCarrera'];
    public $timestamps = false;
}
