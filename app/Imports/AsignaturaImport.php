<?php

namespace App\Imports;

use App\Carrera;
use App\Asignatura;
use App\Periodo;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;

class AsignaturaImport implements ToCollection
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
    	$chunk = $collection->splice(5);
        $periodo = str_replace(':','',trim($chunk[0][2]));
        $periodo = substr($periodo,1);

        $nombre_periodo = trim($chunk[0][4]);
        $carrera = str_replace(':','',trim($chunk[2][2]));
        $nombre_carrera = trim($chunk[2][4]);

        if(Periodo::where('idPeriodo','=',$periodo)->count() > 0){

        }else{
            $periodo_doc = Periodo::create([
                'idPeriodo' => $periodo,
                'descripcion' => $nombre_periodo,
                'estado' => 0
            ]);
            $periodo_doc->save();
        }

    	if(Carrera::where('idCarrera','=',$carrera)->count() > 0){

    	}else{

    		$carrera_doc = Carrera::create([
    		'idCarrera' => $carrera,
    		'nombre' => $nombre_carrera,
    		]);
    		$carrera_doc->save();
        }
        $chunk = $chunk->splice(6);
        foreach ($chunk as $key => $value) {

            if (Asignatura::where('idAsignatura','=',$value[8])->where('actividad','=',$value[18])->count() > 0) {
                continue;
            }
            else{

                if(count($value[6]) > 5){
                    continue;
                }else{

                    if(strlen($value[5]) == 1){
                        $codigo_asig = $value[4].'00'.$value[5];
                    }
                    elseif(strlen($value[5]) == 2){
                        $codigo_asig = $value[4].'0'.$value[5];
                    }elseif (strlen($value[5]) >= 3) {
                        $codigo_asig = $value[4].$value[5];
                    }

                    $liga = str_replace(' ','',$value[10]);
                    $asignatura = Asignatura::Create([
                        'idAsignatura' => $value[8],
                        'codigo_asignatura' => $codigo_asig,
                        'nombre' => $value[16],
                        'idCarrera' => $carrera,
                        'semestre' => $periodo,
                        'confirmacion_semestre' => 2,
                        'sede' => $value[1],
                        'Liga' => $liga,
                        'LCruzada' => $value[11],
                        'actividad' => $value[18]
                    ]);
                    $asignatura->save();
                }
            }
        }
    }
}
