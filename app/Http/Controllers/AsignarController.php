<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Malla;
use Illuminate\Support\Arr;

use function Matrix\add;

class AsignarController extends Controller
{
    public function index()
    {
        $mallas = DB::connection('mysql3')->table('mallas')->select('*')->where('Encuesta','=',0)->get();
        $con_encuesta = DB::connection('mysql3')->table('mallas')->select('*')->where('Encuesta','=',1)->get();
        $periodo = DB::connection('mysql3')->table('periodos')->select('*')->get();
        $asignaturas = DB::connection('mysql3')->table('asignaturas')->select('*')->get();

        $listado = array();
        $listado_con_encuestas = array();

        foreach ($mallas as $key => $value) {
            foreach ($asignaturas as $key_asig => $value_asig) {
                if($value->CodAsign == $value_asig->codigo_asignatura){
                    $listado = $listado + array( $key => array('codigo'=> $value_asig->codigo_asignatura,'nombre' => $value->Nombre));
                    break;
                }else{
                    continue;
                }
            }
        }

        foreach ($con_encuesta as $key => $value) {
            foreach ($asignaturas as $key_asig => $value_asig) {
                if($value->CodAsign == $value_asig->codigo_asignatura){
                    $listado_con_encuestas = $listado_con_encuestas + array( $key => array('codigo'=> $value_asig->codigo_asignatura,'nombre' => $value->Nombre));
                    break;
                }else{
                    continue;
                }
            }
        }

        return view('asignar',['sin_encuesta' => $listado ,'con_encuesta' => $listado_con_encuestas,'periodos' => $periodo, 'periodo' => $periodo[0]->idPeriodo,'nombre_pe' => $periodo[0]->descripcion]);
    }

    function cambio_periodo($id)
    {
        $mallas = DB::connection('mysql3')->table('mallas')->select('*')->where('Encuesta','=',0)->get();
        $con_encuesta = DB::connection('mysql3')->table('mallas')->select('*')->where('Encuesta','=',1)->get();
        $periodo = DB::connection('mysql3')->table('periodos')->select('*')->where('idPeriodo',$id)->get();
        $asignaturas = DB::connection('mysql3')->table('asignaturas')->select('*')->where('semestre','=',$id)->get();

        $listado = array();
        $listado_con_encuestas = array();

        foreach ($mallas as $key => $value) {
            foreach ($asignaturas as $key_asig => $value_asig) {
                if($value->CodAsign == $value_asig->codigo_asignatura){
                    $listado = $listado + array( $key => array('codigo'=> $value_asig->codigo_asignatura,'nombre' => $value->Nombre));
                break;
                }
                else{
                    continue;
                }
            }
        }

        foreach ($con_encuesta as $key => $value) {
            foreach ($asignaturas as $key_asig => $value_asig) {
                if($value->CodAsign == $value_asig->codigo_asignatura){
                    $listado_con_encuestas = $listado_con_encuestas + array( $key => array('codigo'=> $value_asig->codigo_asignatura,'nombre' => $value->Nombre));
                    break;
                }else{
                    continue;
                }
            }
        }
        return view('asignar',['sin_encuesta' => $listado ,'con_encuesta' => $listado_con_encuestas,'periodos' => $periodo,'periodo' => $id,'nombre_pe' => $periodo[0]->descripcion]);
    }

    public function asignar_asignatura(Request $request)
    {
        $lista = $request->input();
        array_shift($lista);
        if (empty($lista)) {
            return back()->with('error','realize algun cambio');
        }
        foreach ($lista as $key => $value) {
            $encuesta = explode('/',$key);
            $encuesta = $encuesta[0];
            if($encuesta == 'con_en'){
                $asignatura = explode('-',$value);
                $codigo = explode('/',$asignatura[1]);
                $mallas = Malla::where('CodAsign', $codigo)->update(['Encuesta' => 1]);

            }elseif($encuesta == 'sin_en'){
                $asignatura = explode('-',$value);
                $codigo = explode('/',$asignatura[1]);
                $mallas = Malla::where('CodAsign', $codigo)->update(['Encuesta' => 0]);
            }
        }

        return back()->with('success','se realizaron los cambios correctamente');
    }
}
