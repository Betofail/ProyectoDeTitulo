<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use App\Alumno_seccion;
use App\Seccion_semestre;
use Maatwebsite\Excel\Concerns\ToCollection;

class SeccionImport implements ToCollection
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
    	$chunk = $collection->splice(8);
    	$nrc = str_replace(':','',$chunk[3][1]);
        $nrc = str_replace(' ','',$nrc);

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
    					'resp_encuesta' => 'none'
    				]);
    			}
    		}
    	}
    }
}
