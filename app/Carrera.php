<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Carrera extends Model
{
    protected $connection = 'mysql3';
    public $timestamps = false;
    protected $fillable = ['idCarrera','nombre'];
}
