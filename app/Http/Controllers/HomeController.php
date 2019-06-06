<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    private $rut;
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */

    public function index()
    {
        $user = Auth::user()->email;
        $tipo = DB::connection('mysql')->table('users')->where('email', $user)->value('tipo');
    
        if ($tipo == 'alumno') {
                //datos para alumnos
            $this->rut = DB::connection('mysql')->table('alumno')->where('email',$user)->value('rut');
            $asignatura = DB::connection('mysql')->table('alumno_seccion')
            ->join('seccion_por_semestre',function($join)
            {
                $join->on('alumno_seccion.nrc','=','seccion_por_semestre.idRamo_seccion')
                ->where('alumno_seccion.rut_alumno','=',$this->rut);
            })
            ->join('docente', 'seccion_por_semestre.Docente_idDocente','=','docente.idDocente')
            ->join('asignatura','seccion_por_semestre.Asignatura_idAsignatura', '=', 'asignatura.idAsignatura')
            ->select('seccion_por_semestre.link_encuesta','asignatura.Nombre', 'docente.nombre','asignatura.Semestre','seccion_por_semestre')->get();

            $campus_clinico =DB::connection('mysql')->table('alumno_seccion')
            ->join('seccion_por_semestre',function($join)
            {
                $join->on('alumno_seccion.nrc','=','seccion_por_semestre.idRamo_seccion')
                ->where('alumno_seccion.rut_alumno','=',$this->rut);
            })
            ->join('asignatura','seccion_por_semestre.Asignatura_idAsignatura','=','asignatura.idAsignatura')
            ->join('campus_clinico_seccion','alumno_seccion.idAlumno_seccion','=','campus_clinico_seccion.alumno_seccion')
            ->join('rotacion_por_semestre', 'campus_clinico_seccion.rotacion','=','rotacion_por_semestre.Campus_A_Cumplir')
            ->join('hospital','rotacion_por_semestre.Hospital_idHospital','=','hospital.idHospital')
            ->select('asignatura.nombre','hospital.nombre_hospital','rotacion_por_semestre.fecha_inicio','rotacion_por_semestre.fecha_termino','seccion_por_semestre.link_encuesta')->get();

            //dd($asignatura,$campus_clinico);
            //return view('alumno_home',['asignatura' => $asignatura,
            //'campus_clinico' => $campus_clinico]);

        }else{
                //datos para docentes
            $this->rut = DB::connection('mysql')->table('docente')->where('email',$user)->value('idDocente');//rut del docente
            $asignatura = DB::connection('mysql')->table('seccion_por_semestre')
            ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut)
            ->join('asignatura','seccion_por_semestre.Asignatura_idAsignatura', '=' , 'asignatura.idAsignatura')
            ->orderBy('seccion_por_semestre.numero_seccion')
            ->select('seccion_por_semestre.Periodo_idPeriodo','seccion_por_semestre.numero_seccion','asignatura.Nombre','seccion_por_semestre.actividad','seccion_por_semestre.fecha_inicio_encuesta','seccion_por_semestre.fecha_termino_encuesta')->get();


            //seccion Alumnos Teoricos
            $alumnos_teoricos = DB::connection('mysql')->table('seccion_por_semestre')->join('alumno_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion', '=', 'alumno_seccion.nrc')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->join('alumno','alumno_seccion.rut_alumno','=','alumno.rut')
            ->orderBy('seccion_por_semestre.numero_seccion')
            ->select('seccion_por_semestre.numero_seccion','alumno.rut','alumno.nombre','alumno_seccion.resp_encuesta','seccion_por_semestre.actividad')->get();

            $contador_alumnos = DB::connection('mysql')->table('seccion_por_semestre')->join('alumno_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion', '=', 'alumno_seccion.nrc')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->groupBy('seccion_por_semestre.actividad','seccion_por_semestre.numero_seccion')
            ->orderBy('seccion_por_semestre.numero_seccion')
            ->select(DB::raw('count(alumno_seccion.nrc) as cantidad_seccion'),'seccion_por_semestre.numero_seccion','seccion_por_semestre.actividad')->get();
            

            //datos Campus clinico Docente
            $campus_clinico = DB::connection('mysql')->table('seccion_por_semestre')
           ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->join('campus_decreto','seccion_por_semestre.Asignatura_idAsignatura','=','campus_decreto.idAsignatura')
            ->orderBy('campus_clinico_seccion.nrc')
            ->select('seccion_por_semestre.Periodo_idPeriodo','campus_clinico_seccion.nrc','campus_decreto.nombre_asignatura')
            ->distinct()
            ->get();


            //seccion Alumnos campus clinicos
            $alumnos_clinicos = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->join('alumno_seccion','campus_clinico_seccion.alumno_seccion','=','alumno_seccion.idAlumno_seccion')
            ->join('alumno','alumno.rut','=','alumno_seccion.rut_alumno')
            ->orderBy('alumno.rut')
            ->select(DB::raw('distinct(alumno.rut) as rut'),'campus_clinico_seccion.nrc','alumno.nombre')->get();

            $contador_alumnos_clinicos = DB::connection('mysql')->table('seccion_por_semestre')
             ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->join('alumno_seccion','campus_clinico_seccion.alumno_seccion','=','alumno_seccion.idAlumno_seccion')
            ->join('alumno','alumno.rut','=','alumno_seccion.rut_alumno')
            ->groupBy('campus_clinico_seccion.nrc')
            ->select(DB::raw('count(distinct(alumno.rut)) as cant_alumnos_cli'),'campus_clinico_seccion.nrc')->get();
 


            //seccion docente campus clinicos
            $docentes_clinicos = DB::connection('mysql')->table('seccion_por_semestre')
             ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->join('profesor_seccion','campus_clinico_seccion.idProfesor_seccion','profesor_seccion.idProfesor_Campus_clinico')
            ->join('docente','docente.idDocente','=','profesor_seccion.Docente_idDocente')
            ->select('campus_clinico_seccion.nrc','docente.nombre','docente.idDocente')
            ->distinct()
            ->get();

            $contador_docentes_clinicos = DB::connection('mysql')->table('seccion_por_semestre')
             ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->groupBy('campus_clinico_seccion.nrc')
            ->select(DB::raw('count(distinct(campus_clinico_seccion.idProfesor_seccion)) as cant_profesor'),'campus_clinico_seccion.nrc')->get();

            //seccion rotaciones campus clinico
            $rotaciones = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->join('profesor_seccion','campus_clinico_seccion.idProfesor_seccion','profesor_seccion.idProfesor_Campus_clinico')
            ->join('docente','docente.idDocente','=','profesor_seccion.Docente_idDocente')
            ->join('rotacion_por_semestre','campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idRotacion_campus_clinico')
            ->join('campus_decreto','rotacion_por_semestre.Campus_A_Cumplir','=','campus_decreto.idCampus_A_Cumplir')
            ->join('hospital','rotacion_por_semestre.Hospital_idHospital','=','hospital.idHospital')
            ->orderBy('hospital.nombre_hospital')
            ->groupBy('seccion_por_semestre.Periodo_idPeriodo','campus_clinico_seccion.nrc','campus_decreto.nombre_asignatura','hospital.nombre_hospital','rotacion_por_semestre.fecha_inicio','rotacion_por_semestre.fecha_termino','rotacion_por_semestre.fecha_inicio_encuesta','docente.nombre')
            ->select(DB::raw('count(campus_clinico_seccion.alumno_seccion) as numero_alumno'),'seccion_por_semestre.Periodo_idPeriodo','campus_clinico_seccion.nrc','campus_decreto.nombre_asignatura','hospital.nombre_hospital','rotacion_por_semestre.fecha_inicio','rotacion_por_semestre.fecha_termino','rotacion_por_semestre.fecha_inicio_encuesta','docente.nombre')->get();

            $contador_rotaciones = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->join('rotacion_por_semestre','campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idRotacion_campus_clinico')
            ->groupBy('rotacion_por_semestre.Campus_A_Cumplir')
            ->select(DB::raw('count(rotacion_por_semestre.Campus_A_cumplir) as count_campus'),'rotacion_por_semestre.Campus_A_Cumplir')->get();


        //dd($campus_clinico,$alumnos_clinicos,$docentes_clinicos,$contador_rotaciones,$rotaciones);
        //dd($docentes_clinicos,$contador_rotaciones,$rotaciones);
        //dd($contador_alumnos_clinicos,$contador_docentes_clinicos);
        //dd($alumnos_teoricos,$asignatura,$contador_alumnos);

        return view('home',['asignatura' => $asignatura,
            'alumnos_teoricos' => $alumnos_teoricos,
            'cantidad_teoricos' => $contador_alumnos,
            'alumnos_clinicos' => $alumnos_clinicos,
            'contador_alumnos_clinicos' => $contador_alumnos_clinicos,
            'campus_clinico' => $campus_clinico,
            'docentes_clinicos' => $docentes_clinicos,
            'contador_docentes_clinicos' => $contador_docentes_clinicos,
            'rotaciones' => $rotaciones,
            'contador_rotaciones' => $contador_rotaciones]);
        }
    }
}

