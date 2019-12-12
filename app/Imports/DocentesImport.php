<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use App\Docente;
use App\User;
use App\Seccion_semestre;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;

class DocentesImport implements ToCollection
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {
        $chunk = $collection->splice(11);

        foreach ($chunk as $key => $value) {
            if (Str::contains($value[14],'/')) {
                $arreglo = str_replace(' ','',explode('/',$value[14]));
                $nombres = str_replace(' ','',explode('/',$value[15]));
                for ($i=0; $i < count($arreglo); $i++) {
                    if (Docente::where('rut','=',$arreglo[$i])->count() > 0) {
                        $seccion = Seccion_semestre::create([
                            'idPeriodo' => $value[3],
                            'idDocente' => $arreglo[$i],
                            'link_encuesta' => 'none',
                            'nrc' => $value[8],
                            'actividad' => $value[18]
                        ]);
                        $seccion->save();
                        continue;
                    }else{
                        $docente = Docente::create([
                            'rut' => $arreglo[$i],
                            'nombre' => $nombres[$i],
                            'email' => 'none'
                        ]);
                        $docente->save();
                        $seccion = Seccion_semestre::create([
                            'idPeriodo' => $value[3],
                            'idDocente' => $arreglo[$i],
                            'link_encuesta' => 'none',
                            'nrc' => $value[8],
                            'actividad' => $value[18]
                        ]);
                        $seccion->save();
                    }
                }
            }
            elseif($value[14] == null or $value[15] == null){
                $seccion = Seccion_semestre::create([
                    'idPeriodo' => $value[3],
                    'idDocente' => "Por Definir",
                    'link_encuesta' => 'none',
                    'nrc' => $value[8],
                    'actividad' => $value[18]
                ]);
                $seccion->save();
            }
            else{
                if(Docente::where('rut','=',$value[14])->count() > 0){
                    $seccion = Seccion_semestre::create([
                        'idPeriodo' => $value[3],
                        'idDocente' => $value[14],
                        'link_encuesta' => 'none',
                        'nrc' => $value[8],
                        'actividad' => $value[18]
                    ]);
                    $seccion->save();
                    continue;
                }else{
                    $docente = Docente::create([
                        'rut' => $value[14],
                        'nombre' => $value[15],
                        'email' => "none"
                    ]);
                    $docente->save();

                }
            }
        }

    }
}
