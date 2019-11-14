<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Periodo extends Model
{
    protected $connection = 'mysql3';
    protected $fillable = ['idPeriodo','descripcion','estado'];
    public $timestamps = false;
}