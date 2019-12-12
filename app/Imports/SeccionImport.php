<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use App\Alumno_seccion;
use App\Docente;
use App\Seccion_semestre;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class SeccionImport implements ToCollection
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        $chunk = $collection->splice(8);
        $nombre = str_replace(':','',$chunk[5][1]);
        $nombre = explode(' ',$nombre);

    	$nrc = str_replace(':','',$chunk[3][1]);
        $nrc = str_replace(' ','',$nrc);
        $query = DB::connection('mysql3')->table('docentes')->select('docentes.rut')->join('seccion_semestres','seccion_semestres.idDocente','=','docentes.rut')
        ->where([['docentes.nombre','like','%'.$nombre[1].'%'],
        ['seccion_semestres.nrc','=',$nrc]])->first();

    	foreach ($chunk as $key => $value) {
    	   	if ($value[3] == null) {
    			continue;
    		}else{
    			if ($value[1] == 'RUT') {
    				continue;
    			}else{
    				Alumno_seccion::create([
    					'rut_alumno' => $value[1],
    					'nrc' => $nrc,
                        'resp_encuesta' => 'none',
                        'idDocente' => $query->rut,
                        'entrega_rubrica' => 0
    				]);
    			}
    		}
    	}
    }
}
