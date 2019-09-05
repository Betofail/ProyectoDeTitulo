<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redirect;

class HomeController extends Controller
{
    private $rut,$periodos,$date;
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
        define( 'LS_BASEURL', 'http://limesurvey.test/index.php');  // adjust this one to your actual LimeSurvey URL
        define( 'LS_USER', 'Alberto' );
        define( 'LS_PASSWORD', 'oviedo83' );

        $periodos = DB::connection('mysql')->table('periodo')
            ->where('estado','>','1')->get()->toArray();

        $this->periodos = $periodos[0]->codigo_periodo;
            
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
            ->select('seccion_por_semestre.numero_seccion','asignatura.Nombre', 'seccion_por_semestre.fecha_inicio_encuesta','seccion_por_semestre.fecha_termino_encuesta','seccion_por_semestre.actividad','seccion_por_semestre.link_encuesta')->get();

            $campus_clinico = DB::connection('mysql')->table('alumno_seccion')
            ->join('seccion_por_semestre',function($join)
            {
                $join->on('alumno_seccion.nrc','=','seccion_por_semestre.idRamo_seccion')
                ->where('alumno_seccion.rut_alumno','=',$this->rut);
            })
            ->join('asignatura','seccion_por_semestre.Asignatura_idAsignatura','=','asignatura.idAsignatura')
            ->join('campus_clinico_seccion','alumno_seccion.idAlumno_seccion','=','campus_clinico_seccion.alumno_seccion')
            ->join('rotacion_por_semestre', 'campus_clinico_seccion.rotacion','=','rotacion_por_semestre.Campus_A_Cumplir')
            ->join('campus_decreto','seccion_por_semestre.Asignatura_idAsignatura','=','campus_decreto.idAsignatura')
            ->orderBy('campus_clinico_seccion.nrc')
            ->select('seccion_por_semestre.Periodo_idPeriodo','campus_clinico_seccion.nrc','campus_decreto.nombre_asignatura')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos)
            ->distinct()
            ->get();

             $rotaciones = DB::connection('mysql')->table('alumno_seccion')
            ->join('campus_clinico_seccion',function($join)
            {
                $join->on('alumno_seccion.idAlumno_seccion','=','campus_clinico_seccion.alumno_seccion')
                ->where('alumno_seccion.rut_alumno','=',$this->rut);
            })
            ->join('seccion_por_semestre','alumno_seccion.nrc','seccion_por_semestre.idRamo_seccion')
            ->join('profesor_seccion','campus_clinico_seccion.idProfesor_seccion','profesor_seccion.idProfesor_Campus_clinico')
            ->join('docente','docente.idDocente','=','profesor_seccion.Docente_idDocente')
            ->join('rotacion_por_semestre','campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idRotacion_campus_clinico')
            ->join('campus_decreto','rotacion_por_semestre.Campus_A_Cumplir','=','campus_decreto.idCampus_A_Cumplir')
            ->join('hospital','rotacion_por_semestre.Hospital_idHospital','=','hospital.idHospital')
            ->orderBy('hospital.nombre_hospital')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos)
            ->groupBy('campus_clinico_seccion.link_encuesta','seccion_por_semestre.Periodo_idPeriodo','campus_clinico_seccion.nrc','campus_decreto.nombre_asignatura','hospital.nombre_hospital','rotacion_por_semestre.fecha_inicio','rotacion_por_semestre.fecha_termino','rotacion_por_semestre.fecha_inicio_encuesta','docente.nombre')
            ->select(DB::raw('count(campus_clinico_seccion.alumno_seccion) as numero_alumno'),'campus_clinico_seccion.link_encuesta','seccion_por_semestre.Periodo_idPeriodo','campus_clinico_seccion.nrc','campus_decreto.nombre_asignatura','hospital.nombre_hospital','rotacion_por_semestre.fecha_inicio','rotacion_por_semestre.fecha_termino','rotacion_por_semestre.fecha_inicio_encuesta','docente.nombre')->get();

        $surveys_ids = [];
        $count = 0;
        $estatus = [];

            foreach ($asignatura as $key => $value) {
                if ($value->link_encuesta == 'none') {
                    continue;
                }else{
                    $survey_id= Str::before($value->link_encuesta,'?');
                    $survey_id = Str::after($survey_id,'http://limesurvey.test/index.php/');

                    $surveys_ids[$count] = ['id' => $survey_id, 'nrc' => $value->numero_seccion];
                    $count++;
                }
            }

        // instantiate a new client
        $myJSONRPCClient = new \org\jsonrpcphp\JsonRPCClient( LS_BASEURL.'/admin/remotecontrol' );

        // receive session key
        $sessionKey= $myJSONRPCClient->get_session_key( LS_USER, LS_PASSWORD );

        $aConditions = array('email' => Auth::user()->email);

        $attributes = ["completed","usesleft"];

        $count = 0;

        foreach ($surveys_ids as $key => $value) {
            $list_participants = $myJSONRPCClient->list_participants($sessionKey,$value['id'],0,100,false,$attributes,$aConditions);
            if(!isset($list_participants[0])){
                continue;
            }else{
                $estatus[$count] = ['nrc' => $value['nrc'], 'estado' => $list_participants[0]['completed']];
                $count++;
            }
        }
        
        $myJSONRPCClient->release_session_key( $sessionKey );


        return view('home',[
            'tipo' => $tipo,
            'periodos' => $periodos,
            'code_periodo' =>$this->periodos,
            'campus_clinico' => $campus_clinico,
            'asignatura' => $asignatura,
            'rotaciones' => $rotaciones,
            'estados' => $estatus]);

        }else if ($tipo == 'PA') {

            $periodos = DB::connection('mysql')->table('periodo')
            ->where('estado','>','1')->get()->toArray();

            $this->periodos = $periodos[0]->codigo_periodo;
            
                //datos para docentes
            $this->rut = DB::connection('mysql')->table('docente')->where('email',$user)->value('idDocente');//rut del docente
            
             $asignatura = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('asignatura',function($join)
            {
                $join->on('seccion_por_semestre.Asignatura_idAsignatura', '=' , 'asignatura.idAsignatura')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->orderBy('seccion_por_semestre.numero_seccion')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos)
            ->select('seccion_por_semestre.Periodo_idPeriodo','seccion_por_semestre.numero_seccion','asignatura.Nombre','seccion_por_semestre.actividad','seccion_por_semestre.fecha_inicio_encuesta','seccion_por_semestre.fecha_termino_encuesta')->get();

            //seccion Alumnos Teoricos
            $alumnos_teoricos = DB::connection('mysql')->table('seccion_por_semestre')->join('alumno_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion', '=', 'alumno_seccion.nrc')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->join('alumno','alumno_seccion.rut_alumno','=','alumno.rut')
            ->orderBy('seccion_por_semestre.numero_seccion')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos)
            ->select('seccion_por_semestre.numero_seccion','alumno.rut','alumno.nombre','alumno_seccion.resp_encuesta','seccion_por_semestre.actividad')->get();

            $contador_alumnos = DB::connection('mysql')->table('seccion_por_semestre')->join('alumno_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion', '=', 'alumno_seccion.nrc')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->groupBy('seccion_por_semestre.actividad','seccion_por_semestre.numero_seccion')
            ->orderBy('seccion_por_semestre.numero_seccion')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos)
            ->select(DB::raw('count(alumno_seccion.nrc) as cantidad_seccion,count(alumno_seccion.resp_encuesta) as resp_encuesta')
                ,'seccion_por_semestre.numero_seccion','seccion_por_semestre.actividad')->get();

            $respuestas_teoricas = DB::connection('mysql')->table('seccion_por_semestre')->join('alumno_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion', '=', 'alumno_seccion.nrc')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->groupBy('seccion_por_semestre.actividad','seccion_por_semestre.numero_seccion')
            ->orderBy('seccion_por_semestre.numero_seccion')
            ->where([['seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos],
            ['alumno_seccion.resp_encuesta','=','si']])
            ->select(DB::raw('count(alumno_seccion.resp_encuesta) as resp_encuesta')
                ,'seccion_por_semestre.numero_seccion','seccion_por_semestre.actividad')->get();

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
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos)
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
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos)
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
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos)
            ->select(DB::raw('count(distinct(alumno.rut)) as cant_alumnos_cli')
                ,'campus_clinico_seccion.nrc')->get();
            
            $respuestas_clinicas = DB::connection('mysql')->table('seccion_por_semestre')
             ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->join('alumno_seccion','campus_clinico_seccion.alumno_seccion','=','alumno_seccion.idAlumno_seccion')
            ->join('alumno','alumno.rut','=','alumno_seccion.rut_alumno')
            ->groupBy('campus_clinico_seccion.nrc')
            ->where([['seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos],
                ['campus_clinico_seccion.res_encuesta','=','si']])
            ->select(DB::raw('count(campus_clinico_seccion.res_encuesta) as resp_encuesta')
                ,'campus_clinico_seccion.nrc')->get();

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
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos)
            ->distinct()
            ->get();

            $contador_docentes_clinicos = DB::connection('mysql')->table('seccion_por_semestre')
             ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->groupBy('campus_clinico_seccion.nrc')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos)
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
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos)
            ->groupBy('campus_clinico_seccion.rotacion','seccion_por_semestre.Periodo_idPeriodo','campus_clinico_seccion.nrc','campus_decreto.nombre_asignatura','hospital.nombre_hospital','rotacion_por_semestre.fecha_inicio','rotacion_por_semestre.fecha_termino','rotacion_por_semestre.fecha_inicio_encuesta','docente.nombre')

            ->select(DB::raw('count(campus_clinico_seccion.alumno_seccion) as numero_alumno'),'campus_clinico_seccion.rotacion','seccion_por_semestre.Periodo_idPeriodo','campus_clinico_seccion.nrc','campus_decreto.nombre_asignatura','hospital.nombre_hospital','rotacion_por_semestre.fecha_inicio','rotacion_por_semestre.fecha_termino','rotacion_por_semestre.fecha_inicio_encuesta','docente.nombre')->get();

            $contador_rotaciones = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->join('rotacion_por_semestre','campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idRotacion_campus_clinico')
            ->groupBy('rotacion_por_semestre.Campus_A_Cumplir')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos)
            ->select(DB::raw('count(rotacion_por_semestre.Campus_A_cumplir) as count_campus'),'rotacion_por_semestre.Campus_A_Cumplir')->get();

            $respuestas_rotaciones = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->join('profesor_seccion','campus_clinico_seccion.idProfesor_seccion','profesor_seccion.idProfesor_Campus_clinico')
            ->join('docente','docente.idDocente','=','profesor_seccion.Docente_idDocente')
            ->join('rotacion_por_semestre','campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idRotacion_campus_clinico')
            ->join('campus_decreto','rotacion_por_semestre.Campus_A_Cumplir','=','campus_decreto.idCampus_A_Cumplir')
            ->where([['campus_clinico_seccion.res_encuesta','=','si'],
            ['seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos]])
            ->groupBy('campus_clinico_seccion.nrc','campus_clinico_seccion.rotacion')
            ->select(DB::raw('count(campus_clinico_seccion.res_encuesta) as resp_encuesta'),'campus_clinico_seccion.nrc','campus_clinico_seccion.rotacion')->get();

            $entregas_rubrica = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->join('profesor_seccion','campus_clinico_seccion.idProfesor_seccion','profesor_seccion.idProfesor_Campus_clinico')
            ->join('docente','docente.idDocente','=','profesor_seccion.Docente_idDocente')
            ->join('rotacion_por_semestre','campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idRotacion_campus_clinico')
            ->where([['campus_clinico_seccion.entrego_rubrica','=','si'],
            ['seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos]])
            ->groupBy('campus_clinico_seccion.nrc')
            ->select(DB::raw('count(campus_clinico_seccion.entrego_rubrica) as entrego_rubrica'),'campus_clinico_seccion.nrc')->get();

            $rotaciones_rubrica = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->join('profesor_seccion','campus_clinico_seccion.idProfesor_seccion','profesor_seccion.idProfesor_Campus_clinico')
            ->join('docente','docente.idDocente','=','profesor_seccion.Docente_idDocente')
            ->join('rotacion_por_semestre','campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idRotacion_campus_clinico')
            ->where([['campus_clinico_seccion.entrego_rubrica','=','si'],
            ['seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos]])
            ->groupBy('campus_clinico_seccion.nrc','campus_clinico_seccion.rotacion')
            ->select(DB::raw('count(campus_clinico_seccion.entrego_rubrica) as entrego_rubrica'),'campus_clinico_seccion.nrc','campus_clinico_seccion.rotacion')->get();

        //dd($campus_clinico,$alumnos_clinicos,$docentes_clinicos,$contador_rotaciones,$rotaciones);
        //dd($docentes_clinicos,$contador_rotaciones,$rotaciones);
        //dd($contador_alumnos_clinicos,$contador_docentes_clinicos);
        //dd($alumnos_teoricos,$asignatura,$contador_alumnos);
        //dd($respuestas_clinicas,$respuestas_teoricas);

        return view('home',[
            'tipo' => $tipo,
            'periodos' => $periodos,
            'code_periodo' =>$this->periodos,
            'asignatura' => $asignatura,
            'alumnos_teoricos' => $alumnos_teoricos,
            'cantidad_teoricos' => $contador_alumnos,
            'alumnos_clinicos' => $alumnos_clinicos,
            'contador_alumnos_clinicos' => $contador_alumnos_clinicos,
            'campus_clinico' => $campus_clinico,
            'docentes_clinicos' => $docentes_clinicos,
            'contador_docentes_clinicos' => $contador_docentes_clinicos,
            'rotaciones' => $rotaciones,
            'contador_rotaciones' => $contador_rotaciones,
            'respuestas_teoricas' => $respuestas_teoricas,
            'respuestas_clinicas' => $respuestas_clinicas,
            'respuestas_rotaciones' => $respuestas_rotaciones,
            'entrego_rubrica' => $entregas_rubrica,
            'rotaciones_rubrica' => $rotaciones_rubrica]);
        }

        else if ($tipo == 'SA') {
            $periodos = DB::connection('mysql')->table('periodo')
            ->where('estado','>','0')->get()->toArray();

            $this->periodos = $periodos[0]->codigo_periodo;

            //Asignaturas Teoricas
            $asignatura = DB::connection('mysql')->table('seccion_por_semestre')
           
            ->join('asignatura','seccion_por_semestre.Asignatura_idAsignatura', '=' , 'asignatura.idAsignatura')
            ->orderBy('seccion_por_semestre.numero_seccion')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos)
            ->select('seccion_por_semestre.Periodo_idPeriodo','seccion_por_semestre.numero_seccion','asignatura.Nombre','seccion_por_semestre.actividad','seccion_por_semestre.fecha_inicio_encuesta','seccion_por_semestre.fecha_termino_encuesta')->get();


            $contador_alumnos = DB::connection('mysql')->table('seccion_por_semestre')->join('alumno_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion', '=', 'alumno_seccion.nrc');
                
            })
            ->groupBy('seccion_por_semestre.actividad','seccion_por_semestre.numero_seccion')
            ->orderBy('seccion_por_semestre.numero_seccion')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos)
            ->select(DB::raw('count(alumno_seccion.nrc) as cantidad_seccion'),'seccion_por_semestre.numero_seccion','seccion_por_semestre.actividad')->get();

            $respuestas_teoricas = DB::connection('mysql')->table('seccion_por_semestre')->join('alumno_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion', '=', 'alumno_seccion.nrc');
                
            })
            ->groupBy('seccion_por_semestre.actividad','seccion_por_semestre.numero_seccion')
            ->orderBy('seccion_por_semestre.numero_seccion')
            ->where([['seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos],
            ['alumno_seccion.resp_encuesta','=','si']])
            ->select(DB::raw('count(alumno_seccion.resp_encuesta) as resp_encuesta')
                ,'seccion_por_semestre.numero_seccion','seccion_por_semestre.actividad')->get();

             //datos Campus clinico Docente
            $campus_clinico = DB::connection('mysql')->table('seccion_por_semestre')
           ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre');
                
            })
            ->join('campus_decreto','seccion_por_semestre.Asignatura_idAsignatura','=','campus_decreto.idAsignatura')
            ->orderBy('campus_clinico_seccion.nrc')
            ->select('seccion_por_semestre.Periodo_idPeriodo','campus_clinico_seccion.nrc','campus_decreto.nombre_asignatura')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos)
            ->distinct()
            ->get();

            $contador_alumnos_clinicos = DB::connection('mysql')->table('seccion_por_semestre')
             ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre');
                
            })
            ->join('alumno_seccion','campus_clinico_seccion.alumno_seccion','=','alumno_seccion.idAlumno_seccion')
            ->join('alumno','alumno.rut','=','alumno_seccion.rut_alumno')
            ->groupBy('campus_clinico_seccion.nrc')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos)
            ->select(DB::raw('count(distinct(alumno.rut)) as cant_alumnos_cli'),'campus_clinico_seccion.nrc')->get();

             $contador_docentes_clinicos = DB::connection('mysql')->table('seccion_por_semestre')
             ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre');
                
            })
            ->groupBy('campus_clinico_seccion.nrc')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos)
            ->select(DB::raw('count(distinct(campus_clinico_seccion.idProfesor_seccion)) as cant_profesor'),'campus_clinico_seccion.nrc')->get();

            $respuestas_clinicas = DB::connection('mysql')->table('seccion_por_semestre')
             ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre');
               
            })
            ->join('alumno_seccion','campus_clinico_seccion.alumno_seccion','=','alumno_seccion.idAlumno_seccion')
            ->join('alumno','alumno.rut','=','alumno_seccion.rut_alumno')
            ->groupBy('campus_clinico_seccion.nrc')
            ->where([['seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos],
                ['campus_clinico_seccion.res_encuesta','=','si']])
            ->select(DB::raw('count(campus_clinico_seccion.res_encuesta) as resp_encuesta')
                ,'campus_clinico_seccion.nrc')->get();

             $rotaciones = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre');
                
            })
            ->join('profesor_seccion','campus_clinico_seccion.idProfesor_seccion','profesor_seccion.idProfesor_Campus_clinico')
            ->join('docente','docente.idDocente','=','profesor_seccion.Docente_idDocente')
            ->join('rotacion_por_semestre','campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idRotacion_campus_clinico')
            ->join('campus_decreto','rotacion_por_semestre.Campus_A_Cumplir','=','campus_decreto.idCampus_A_Cumplir')
            ->join('hospital','rotacion_por_semestre.Hospital_idHospital','=','hospital.idHospital')
            ->orderBy('hospital.nombre_hospital')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos)
            ->groupBy('campus_clinico_seccion.rotacion','seccion_por_semestre.Periodo_idPeriodo','campus_clinico_seccion.nrc','campus_decreto.nombre_asignatura','hospital.nombre_hospital','rotacion_por_semestre.fecha_inicio','rotacion_por_semestre.fecha_termino','rotacion_por_semestre.fecha_inicio_encuesta','docente.nombre')

            ->select(DB::raw('count(campus_clinico_seccion.alumno_seccion) as numero_alumno'),'campus_clinico_seccion.rotacion','seccion_por_semestre.Periodo_idPeriodo','campus_clinico_seccion.nrc','campus_decreto.nombre_asignatura','hospital.nombre_hospital','rotacion_por_semestre.fecha_inicio','rotacion_por_semestre.fecha_termino','rotacion_por_semestre.fecha_inicio_encuesta','docente.nombre')->get();

             $contador_rotaciones = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre');
            })
            ->join('rotacion_por_semestre','campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idRotacion_campus_clinico')
            ->groupBy('rotacion_por_semestre.Campus_A_Cumplir')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos)
            ->select(DB::raw('count(rotacion_por_semestre.Campus_A_cumplir) as count_campus'),'rotacion_por_semestre.Campus_A_Cumplir')->get();

              $respuestas_rotaciones = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre');
            })
            ->join('profesor_seccion','campus_clinico_seccion.idProfesor_seccion','profesor_seccion.idProfesor_Campus_clinico')
            ->join('docente','docente.idDocente','=','profesor_seccion.Docente_idDocente')
            ->join('rotacion_por_semestre','campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idRotacion_campus_clinico')
            ->join('campus_decreto','rotacion_por_semestre.Campus_A_Cumplir','=','campus_decreto.idCampus_A_Cumplir')
            ->where([['campus_clinico_seccion.res_encuesta','=','si'],
            ['seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos]])
            ->groupBy('campus_clinico_seccion.nrc','campus_clinico_seccion.rotacion')
            ->select(DB::raw('count(campus_clinico_seccion.res_encuesta) as resp_encuesta'),'campus_clinico_seccion.nrc','campus_clinico_seccion.rotacion')->get();

             $entregas_rubrica = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre');
            })
            ->join('profesor_seccion','campus_clinico_seccion.idProfesor_seccion','profesor_seccion.idProfesor_Campus_clinico')
            ->join('docente','docente.idDocente','=','profesor_seccion.Docente_idDocente')
            ->join('rotacion_por_semestre','campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idRotacion_campus_clinico')
            ->where([['campus_clinico_seccion.entrego_rubrica','=','si'],
            ['seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos]])
            ->groupBy('campus_clinico_seccion.nrc')
            ->select(DB::raw('count(campus_clinico_seccion.entrego_rubrica) as entrego_rubrica'),'campus_clinico_seccion.nrc')->get();

            $rotaciones_rubrica = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre');
            })
            ->join('profesor_seccion','campus_clinico_seccion.idProfesor_seccion','profesor_seccion.idProfesor_Campus_clinico')
            ->join('docente','docente.idDocente','=','profesor_seccion.Docente_idDocente')
            ->join('rotacion_por_semestre','campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idRotacion_campus_clinico')
            ->where([['campus_clinico_seccion.entrego_rubrica','=','si'],
            ['seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos]])
            ->groupBy('campus_clinico_seccion.nrc','campus_clinico_seccion.rotacion')
            ->select(DB::raw('count(campus_clinico_seccion.entrego_rubrica) as entrego_rubrica'),'campus_clinico_seccion.nrc','campus_clinico_seccion.rotacion')->get();


            //dd($campus_sa,$num_alumnos_campus_sa,$num_respuestas_campus_sa);
            //dd($rotaciones,$respuestas_rotaciones,$rotaciones_rubrica,$entregas_rubrica);
            
            return view('home',[
            'tipo' => $tipo,
            'periodos' => $periodos,
            'code_periodo' =>$this->periodos,
            'asignatura' => $asignatura,
            
            'cantidad_teoricos' => $contador_alumnos,
            
            'contador_alumnos_clinicos' => $contador_alumnos_clinicos,
            'campus_clinico' => $campus_clinico,
            
            'contador_docentes_clinicos' => $contador_docentes_clinicos,
            'rotaciones' => $rotaciones,
            'contador_rotaciones' => $contador_rotaciones,
            'respuestas_teoricas' => $respuestas_teoricas,
            'respuestas_clinicas' => $respuestas_clinicas,
            'respuestas_rotaciones' => $respuestas_rotaciones,
            'entrego_rubrica' => $entregas_rubrica,
            'rotaciones_rubrica' => $rotaciones_rubrica]);
        }

        else if ($tipo == 'docente') {
            $periodos = DB::connection('mysql')->table('periodo')
            ->where('estado','>','1')->get()->toArray();

            $this->periodos = $periodos[0]->codigo_periodo;
            
            //datos para docentes
            $this->rut = DB::connection('mysql')->table('docente')->where('email',$user)->value('idDocente');//rut del docente

            $asignatura = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('asignatura',function($join)
            {
                $join->on('seccion_por_semestre.Asignatura_idAsignatura', '=' , 'asignatura.idAsignatura')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->orderBy('seccion_por_semestre.numero_seccion')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos)
            ->select('seccion_por_semestre.Periodo_idPeriodo','seccion_por_semestre.numero_seccion','asignatura.Nombre','seccion_por_semestre.actividad','seccion_por_semestre.fecha_inicio_encuesta','seccion_por_semestre.fecha_termino_encuesta')->get();

            //seccion Alumnos Teoricos 
            $contador_alumnos = DB::connection('mysql')->table('seccion_por_semestre')->join('alumno_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion', '=', 'alumno_seccion.nrc')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->groupBy('seccion_por_semestre.actividad','seccion_por_semestre.numero_seccion')
            ->orderBy('seccion_por_semestre.numero_seccion')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos)
            ->select(DB::raw('count(alumno_seccion.nrc) as cantidad_seccion'),'seccion_por_semestre.numero_seccion','seccion_por_semestre.actividad')->get();
            

            //datos Campus clinico Docente
            $campus_clinico = DB::connection('mysql')->table('rotacion_por_semestre')
           ->join('campus_clinico_seccion','campus_clinico_seccion.seccion_semestre','rotacion_por_semestre.idRotacion_campus_clinico')
            ->join('profesor_seccion',function($join){
                $join->on('profesor_seccion.idProfesor_Campus_clinico','=','campus_clinico_seccion.idProfesor_seccion')
                ->where('profesor_seccion.Docente_idDocente','=',$this->rut);
            })
            ->join('seccion_por_semestre','seccion_por_semestre.idRamo_seccion','campus_clinico_seccion.seccion_semestre')
            ->join('campus_decreto','seccion_por_semestre.Asignatura_idAsignatura','=','campus_decreto.idAsignatura')
            ->orderBy('campus_clinico_seccion.nrc')
            ->select('seccion_por_semestre.Periodo_idPeriodo','campus_clinico_seccion.nrc','campus_decreto.nombre_asignatura')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos)
            ->distinct()
            ->get();

            //seccion Alumnos campus clinicos
            $contador_alumnos_clinicos = DB::connection('mysql')->table('seccion_por_semestre')
             ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre');
            })
            ->join('alumno_seccion','campus_clinico_seccion.alumno_seccion','=','alumno_seccion.idAlumno_seccion')
            ->join('alumno','alumno.rut','=','alumno_seccion.rut_alumno')
            ->join('profesor_seccion','profesor_seccion.idProfesor_Campus_clinico', 'campus_clinico_seccion.idProfesor_seccion')
            ->groupBy('campus_clinico_seccion.nrc')
            ->where([['seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos],
            ['profesor_seccion.Docente_idDocente','=',$this->rut]])
             ->select(DB::raw('count(distinct(alumno.rut)) as cant_alumnos_cli, count(campus_clinico_seccion.res_encuesta) as resp_encuesta')
                ,'campus_clinico_seccion.nrc')->get();

            $contador_docentes_clinicos = DB::connection('mysql')->table('seccion_por_semestre')
             ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre');
            })
            ->join('profesor_seccion','profesor_seccion.idProfesor_Campus_clinico','campus_clinico_seccion.idProfesor_seccion')
            ->groupBy('campus_clinico_seccion.nrc')
            ->where([['seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos],
                    ['profesor_seccion.Docente_idDocente','=',$this->rut]])
            ->select(DB::raw('count(distinct(campus_clinico_seccion.idProfesor_seccion)) as cant_profesor'),'campus_clinico_seccion.nrc')->get();

            //seccion rotaciones campus clinico
            $rotaciones = DB::connection('mysql')->table('rotacion_por_semestre')
            ->join('campus_decreto','campus_decreto.idCampus_A_Cumplir','rotacion_por_semestre.Campus_A_Cumplir')
            ->join('campus_clinico_seccion','rotacion_por_semestre.idRotacion_campus_clinico','campus_clinico_seccion.rotacion')
            ->join('profesor_seccion','campus_clinico_seccion.idProfesor_seccion','profesor_seccion.idProfesor_Campus_clinico')
            ->join('docente','docente.idDocente','profesor_seccion.Docente_idDocente')
            ->join('seccion_por_semestre','seccion_por_semestre.idRamo_seccion','campus_clinico_seccion.seccion_semestre')
            ->join('hospital','hospital.idHospital','rotacion_por_semestre.Hospital_idHospital')
            ->where([['profesor_seccion.Docente_idDocente','=',$this->rut],
            ['seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos]])
            ->groupBy('campus_clinico_seccion.rotacion','campus_clinico_seccion.nrc','campus_decreto.nombre_asignatura',
            'hospital.nombre_hospital','rotacion_por_semestre.fecha_inicio','rotacion_por_semestre.fecha_termino',
            'rotacion_por_semestre.fecha_inicio_encuesta','docente.nombre')
            ->select(DB::raw('count(campus_clinico_seccion.alumno_seccion) as numero_alumno'),'campus_clinico_seccion.rotacion','campus_clinico_seccion.nrc','campus_decreto.nombre_asignatura',
            'hospital.nombre_hospital','rotacion_por_semestre.fecha_inicio','rotacion_por_semestre.fecha_termino',
            'rotacion_por_semestre.fecha_inicio_encuesta','docente.nombre')->get();

            $contador_rotaciones = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->join('rotacion_por_semestre','campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idRotacion_campus_clinico')
            ->groupBy('rotacion_por_semestre.Campus_A_Cumplir')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos)
            ->select(DB::raw('count(rotacion_por_semestre.Campus_A_cumplir) as count_campus'),'rotacion_por_semestre.Campus_A_Cumplir')->get();

            $respuestas_teoricas = DB::connection('mysql')->table('seccion_por_semestre')->join('alumno_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion', '=', 'alumno_seccion.nrc');
                
            })
            ->groupBy('seccion_por_semestre.actividad','seccion_por_semestre.numero_seccion')
            ->orderBy('seccion_por_semestre.numero_seccion')
            ->where([['seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos],
            ['alumno_seccion.resp_encuesta','=','si']])
            ->select(DB::raw('count(alumno_seccion.resp_encuesta) as resp_encuesta')
                ,'seccion_por_semestre.numero_seccion','seccion_por_semestre.actividad')->get();

            $respuestas_rotaciones = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre');
            })
            ->join('profesor_seccion','campus_clinico_seccion.idProfesor_seccion','profesor_seccion.idProfesor_Campus_clinico')
            ->join('docente','docente.idDocente','=','profesor_seccion.Docente_idDocente')
            ->join('rotacion_por_semestre','campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idRotacion_campus_clinico')
            ->join('campus_decreto','rotacion_por_semestre.Campus_A_Cumplir','=','campus_decreto.idCampus_A_Cumplir')
            ->where([['campus_clinico_seccion.res_encuesta','=','si'],
            ['seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos],
            ['profesor_seccion.Docente_idDocente','=',$this->rut]])
            ->groupBy('campus_clinico_seccion.nrc','campus_clinico_seccion.rotacion')
            ->select(DB::raw('count(campus_clinico_seccion.res_encuesta) as resp_encuesta'),'campus_clinico_seccion.nrc','campus_clinico_seccion.rotacion')->get();

             $entregas_rubrica = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre');
            })
            ->join('profesor_seccion','campus_clinico_seccion.idProfesor_seccion','profesor_seccion.idProfesor_Campus_clinico')
            ->join('docente','docente.idDocente','=','profesor_seccion.Docente_idDocente')
            ->join('rotacion_por_semestre','campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idRotacion_campus_clinico')
            ->where([['campus_clinico_seccion.entrego_rubrica','=','si'],
            ['seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos],
            ['profesor_seccion.Docente_idDocente','=',$this->rut]])
            ->groupBy('campus_clinico_seccion.nrc')
            ->select(DB::raw('count(campus_clinico_seccion.entrego_rubrica) as entrego_rubrica'),'campus_clinico_seccion.nrc')->get();

            $rotaciones_rubrica = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre');
            })
            ->join('profesor_seccion','campus_clinico_seccion.idProfesor_seccion','profesor_seccion.idProfesor_Campus_clinico')
            ->join('docente','docente.idDocente','=','profesor_seccion.Docente_idDocente')
            ->join('rotacion_por_semestre','campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idRotacion_campus_clinico')
            ->join('campus_decreto','rotacion_por_semestre.Campus_A_Cumplir','=','campus_decreto.idCampus_A_Cumplir')
            ->where([['campus_clinico_seccion.res_encuesta','=','si'],
            ['seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos],
            ['profesor_seccion.Docente_idDocente','=',$this->rut]])
            ->groupBy('campus_clinico_seccion.nrc','campus_clinico_seccion.rotacion')
            ->select(DB::raw('count(campus_clinico_seccion.entrego_rubrica) as entrego_rubrica'),'campus_clinico_seccion.nrc','campus_clinico_seccion.rotacion')->get();

             $respuestas_clinicas = DB::connection('mysql')->table('seccion_por_semestre')
             ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre');
               
            })
            ->join('alumno_seccion','campus_clinico_seccion.alumno_seccion','=','alumno_seccion.idAlumno_seccion')
            ->join('alumno','alumno.rut','=','alumno_seccion.rut_alumno')
            ->join('profesor_seccion','profesor_seccion.idProfesor_Campus_clinico','campus_clinico_seccion.idProfesor_seccion')
            ->groupBy('campus_clinico_seccion.nrc')
            ->where([['seccion_por_semestre.Periodo_idPeriodo','=',$this->periodos],
                ['campus_clinico_seccion.res_encuesta','=','si'],
                ['profesor_seccion.Docente_idDocente','=',$this->rut]])
            ->select(DB::raw('count(campus_clinico_seccion.res_encuesta) as resp_encuesta')
                ,'campus_clinico_seccion.nrc')->get();
            
        //dd($contador_alumnos_clinicos);
        //dd($campus_clinico,$alumnos_clinicos,$docentes_clinicos,$contador_rotaciones,$rotaciones);
        //dd($docentes_clinicos,$contador_rotaciones,$rotaciones);
        //dd($contador_alumnos_clinicos,$contador_docentes_clinicos);
        //dd($alumnos_teoricos,$asignatura->isEmpty(),$contador_alumnos);
        //dd($contador_alumnos_clinicos,$respuestas_clinicas,$respuestas_rotaciones,$entregas_rubrica,$rotaciones_rubrica);

     return view('home',[
            'tipo' => $tipo,
            'periodos' => $periodos,
            'code_periodo' =>$this->periodos,
            'asignatura' => $asignatura,
            'cantidad_teoricos' => $contador_alumnos,
            'contador_alumnos_clinicos' => $contador_alumnos_clinicos,
            'campus_clinico' => $campus_clinico,
            'contador_docentes_clinicos' => $contador_docentes_clinicos,
            'rotaciones' => $rotaciones,
            'contador_rotaciones' => $contador_rotaciones,
            'respuestas_teoricas' => $respuestas_teoricas,
            'respuestas_clinicas' => $respuestas_clinicas,
            'respuestas_rotaciones' => $respuestas_rotaciones,
            'entrego_rubrica' => $entregas_rubrica,
            'rotaciones_rubrica' => $rotaciones_rubrica]);        
        }  
    }

    public function periodo_pa($id)
    {
        $user = Auth::user()->email;
        $tipo = DB::connection('mysql')->table('users')->where('email', $user)->value('tipo');
        $periodos = DB::connection('mysql')->table('periodo')->where('estado','>','0')->get()->toArray();

            $this->rut = DB::connection('mysql')->table('docente')->where('email',$user)->value('idDocente');//rut del docente
            
             $asignatura = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('asignatura',function($join)
            {
                $join->on('seccion_por_semestre.Asignatura_idAsignatura', '=' , 'asignatura.idAsignatura')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->orderBy('seccion_por_semestre.numero_seccion')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$id)
            ->select('seccion_por_semestre.Periodo_idPeriodo','seccion_por_semestre.numero_seccion','asignatura.Nombre','seccion_por_semestre.actividad','seccion_por_semestre.fecha_inicio_encuesta','seccion_por_semestre.fecha_termino_encuesta')->get();

            //seccion Alumnos Teoricos
            $alumnos_teoricos = DB::connection('mysql')->table('seccion_por_semestre')->join('alumno_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion', '=', 'alumno_seccion.nrc')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->join('alumno','alumno_seccion.rut_alumno','=','alumno.rut')
            ->orderBy('seccion_por_semestre.numero_seccion')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$id)
            ->select('seccion_por_semestre.numero_seccion','alumno.rut','alumno.nombre','alumno_seccion.resp_encuesta','seccion_por_semestre.actividad')->get();

            $contador_alumnos = DB::connection('mysql')->table('seccion_por_semestre')->join('alumno_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion', '=', 'alumno_seccion.nrc')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->groupBy('seccion_por_semestre.actividad','seccion_por_semestre.numero_seccion')
            ->orderBy('seccion_por_semestre.numero_seccion')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$id)
            ->select(DB::raw('count(alumno_seccion.nrc) as cantidad_seccion,count(alumno_seccion.resp_encuesta) as resp_encuesta')
                ,'seccion_por_semestre.numero_seccion','seccion_por_semestre.actividad')->get();

            $respuestas_teoricas = DB::connection('mysql')->table('seccion_por_semestre')->join('alumno_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion', '=', 'alumno_seccion.nrc')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->groupBy('seccion_por_semestre.actividad','seccion_por_semestre.numero_seccion')
            ->orderBy('seccion_por_semestre.numero_seccion')
            ->where([['seccion_por_semestre.Periodo_idPeriodo','=',$id],
            ['alumno_seccion.resp_encuesta','=','si']])
            ->select(DB::raw('count(alumno_seccion.resp_encuesta) as resp_encuesta')
                ,'seccion_por_semestre.numero_seccion','seccion_por_semestre.actividad')->get();

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
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$id)
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
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$id)
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
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$id)
            ->select(DB::raw('count(distinct(alumno.rut)) as cant_alumnos_cli')
                ,'campus_clinico_seccion.nrc')->get();
            
            $respuestas_clinicas = DB::connection('mysql')->table('seccion_por_semestre')
             ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->join('alumno_seccion','campus_clinico_seccion.alumno_seccion','=','alumno_seccion.idAlumno_seccion')
            ->join('alumno','alumno.rut','=','alumno_seccion.rut_alumno')
            ->groupBy('campus_clinico_seccion.nrc')
            ->where([['seccion_por_semestre.Periodo_idPeriodo','=',$id],
                ['campus_clinico_seccion.res_encuesta','=','si']])
            ->select(DB::raw('count(campus_clinico_seccion.res_encuesta) as resp_encuesta')
                ,'campus_clinico_seccion.nrc')->get();

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
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$id)
            ->distinct()
            ->get();

            $contador_docentes_clinicos = DB::connection('mysql')->table('seccion_por_semestre')
             ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->groupBy('campus_clinico_seccion.nrc')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$id)
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
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$id)
            ->groupBy('campus_clinico_seccion.rotacion','seccion_por_semestre.Periodo_idPeriodo','campus_clinico_seccion.nrc','campus_decreto.nombre_asignatura','hospital.nombre_hospital','rotacion_por_semestre.fecha_inicio','rotacion_por_semestre.fecha_termino','rotacion_por_semestre.fecha_inicio_encuesta','docente.nombre')

            ->select(DB::raw('count(campus_clinico_seccion.alumno_seccion) as numero_alumno'),'campus_clinico_seccion.rotacion','seccion_por_semestre.Periodo_idPeriodo','campus_clinico_seccion.nrc','campus_decreto.nombre_asignatura','hospital.nombre_hospital','rotacion_por_semestre.fecha_inicio','rotacion_por_semestre.fecha_termino','rotacion_por_semestre.fecha_inicio_encuesta','docente.nombre')->get();

            $contador_rotaciones = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->join('rotacion_por_semestre','campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idRotacion_campus_clinico')
            ->groupBy('rotacion_por_semestre.Campus_A_Cumplir')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$id)
            ->select(DB::raw('count(rotacion_por_semestre.Campus_A_cumplir) as count_campus'),'rotacion_por_semestre.Campus_A_Cumplir')->get();

            $respuestas_rotaciones = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->join('profesor_seccion','campus_clinico_seccion.idProfesor_seccion','profesor_seccion.idProfesor_Campus_clinico')
            ->join('docente','docente.idDocente','=','profesor_seccion.Docente_idDocente')
            ->join('rotacion_por_semestre','campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idRotacion_campus_clinico')
            ->join('campus_decreto','rotacion_por_semestre.Campus_A_Cumplir','=','campus_decreto.idCampus_A_Cumplir')
            ->where([['campus_clinico_seccion.res_encuesta','=','si'],
            ['seccion_por_semestre.Periodo_idPeriodo','=',$id]])
            ->groupBy('campus_clinico_seccion.nrc','campus_clinico_seccion.rotacion')
            ->select(DB::raw('count(campus_clinico_seccion.res_encuesta) as resp_encuesta'),'campus_clinico_seccion.nrc','campus_clinico_seccion.rotacion')->get();

            $entregas_rubrica = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->join('profesor_seccion','campus_clinico_seccion.idProfesor_seccion','profesor_seccion.idProfesor_Campus_clinico')
            ->join('docente','docente.idDocente','=','profesor_seccion.Docente_idDocente')
            ->join('rotacion_por_semestre','campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idRotacion_campus_clinico')
            ->where([['campus_clinico_seccion.entrego_rubrica','=','si'],
            ['seccion_por_semestre.Periodo_idPeriodo','=',$id]])
            ->groupBy('campus_clinico_seccion.nrc')
            ->select(DB::raw('count(campus_clinico_seccion.entrego_rubrica) as entrego_rubrica'),'campus_clinico_seccion.nrc')->get();

            $rotaciones_rubrica = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->join('profesor_seccion','campus_clinico_seccion.idProfesor_seccion','profesor_seccion.idProfesor_Campus_clinico')
            ->join('docente','docente.idDocente','=','profesor_seccion.Docente_idDocente')
            ->join('rotacion_por_semestre','campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idRotacion_campus_clinico')
            ->where([['campus_clinico_seccion.entrego_rubrica','=','si'],
            ['seccion_por_semestre.Periodo_idPeriodo','=',$id]])
            ->groupBy('campus_clinico_seccion.nrc','campus_clinico_seccion.rotacion')
            ->select(DB::raw('count(campus_clinico_seccion.entrego_rubrica) as entrego_rubrica'),'campus_clinico_seccion.nrc','campus_clinico_seccion.rotacion')->get();

        //dd($campus_clinico,$alumnos_clinicos,$docentes_clinicos,$contador_rotaciones,$rotaciones);
        //dd($docentes_clinicos,$contador_rotaciones,$rotaciones);
        //dd($contador_alumnos_clinicos,$contador_docentes_clinicos);
        //dd($alumnos_teoricos,$asignatura,$contador_alumnos);
        //dd($respuestas_clinicas,$respuestas_teoricas);

        return view('home',[
            'tipo' => $tipo,
            'periodos' => $periodos,
            'code_periodo' =>$id,
            'asignatura' => $asignatura,
            'alumnos_teoricos' => $alumnos_teoricos,
            'cantidad_teoricos' => $contador_alumnos,
            'alumnos_clinicos' => $alumnos_clinicos,
            'contador_alumnos_clinicos' => $contador_alumnos_clinicos,
            'campus_clinico' => $campus_clinico,
            'docentes_clinicos' => $docentes_clinicos,
            'contador_docentes_clinicos' => $contador_docentes_clinicos,
            'rotaciones' => $rotaciones,
            'contador_rotaciones' => $contador_rotaciones,
            'respuestas_teoricas' => $respuestas_teoricas,
            'respuestas_clinicas' => $respuestas_clinicas,
            'respuestas_rotaciones' => $respuestas_rotaciones,
            'entrego_rubrica' => $entregas_rubrica,
            'rotaciones_rubrica' => $rotaciones_rubrica]);
    }

    public function peridos_sa($id)
    {
            $user = Auth::user()->email;
            $tipo = DB::connection('mysql')->table('users')->where('email', $user)->value('tipo');
            $periodos = DB::connection('mysql')->table('periodo')
            ->where('estado','>','0')->get()->toArray();

             //Asignaturas Teoricas
            $asignatura = DB::connection('mysql')->table('seccion_por_semestre')
           
            ->join('asignatura','seccion_por_semestre.Asignatura_idAsignatura', '=' , 'asignatura.idAsignatura')
            ->orderBy('seccion_por_semestre.numero_seccion')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$id)
            ->select('seccion_por_semestre.Periodo_idPeriodo','seccion_por_semestre.numero_seccion','asignatura.Nombre','seccion_por_semestre.actividad','seccion_por_semestre.fecha_inicio_encuesta','seccion_por_semestre.fecha_termino_encuesta')->get();


            $contador_alumnos = DB::connection('mysql')->table('seccion_por_semestre')->join('alumno_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion', '=', 'alumno_seccion.nrc');
                
            })
            ->groupBy('seccion_por_semestre.actividad','seccion_por_semestre.numero_seccion')
            ->orderBy('seccion_por_semestre.numero_seccion')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$id)
            ->select(DB::raw('count(alumno_seccion.nrc) as cantidad_seccion'),'seccion_por_semestre.numero_seccion','seccion_por_semestre.actividad')->get();

            $respuestas_teoricas = DB::connection('mysql')->table('seccion_por_semestre')->join('alumno_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion', '=', 'alumno_seccion.nrc');
                
            })
            ->groupBy('seccion_por_semestre.actividad','seccion_por_semestre.numero_seccion')
            ->orderBy('seccion_por_semestre.numero_seccion')
            ->where([['seccion_por_semestre.Periodo_idPeriodo','=',$id],
            ['alumno_seccion.resp_encuesta','=','si']])
            ->select(DB::raw('count(alumno_seccion.resp_encuesta) as resp_encuesta')
                ,'seccion_por_semestre.numero_seccion','seccion_por_semestre.actividad')->get();

             //datos Campus clinico Docente
            $campus_clinico = DB::connection('mysql')->table('seccion_por_semestre')
           ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre');
                
            })
            ->join('campus_decreto','seccion_por_semestre.Asignatura_idAsignatura','=','campus_decreto.idAsignatura')
            ->orderBy('campus_clinico_seccion.nrc')
            ->select('seccion_por_semestre.Periodo_idPeriodo','campus_clinico_seccion.nrc','campus_decreto.nombre_asignatura')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$id)
            ->distinct()
            ->get();

            $contador_alumnos_clinicos = DB::connection('mysql')->table('seccion_por_semestre')
             ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre');
                
            })
            ->join('alumno_seccion','campus_clinico_seccion.alumno_seccion','=','alumno_seccion.idAlumno_seccion')
            ->join('alumno','alumno.rut','=','alumno_seccion.rut_alumno')
            ->groupBy('campus_clinico_seccion.nrc')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$id)
            ->select(DB::raw('count(distinct(alumno.rut)) as cant_alumnos_cli'),'campus_clinico_seccion.nrc')->get();

             $contador_docentes_clinicos = DB::connection('mysql')->table('seccion_por_semestre')
             ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre');
                
            })
            ->groupBy('campus_clinico_seccion.nrc')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$id)
            ->select(DB::raw('count(distinct(campus_clinico_seccion.idProfesor_seccion)) as cant_profesor'),'campus_clinico_seccion.nrc')->get();

            $respuestas_clinicas = DB::connection('mysql')->table('seccion_por_semestre')
             ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre');
               
            })
            ->join('alumno_seccion','campus_clinico_seccion.alumno_seccion','=','alumno_seccion.idAlumno_seccion')
            ->join('alumno','alumno.rut','=','alumno_seccion.rut_alumno')
            ->groupBy('campus_clinico_seccion.nrc')
            ->where([['seccion_por_semestre.Periodo_idPeriodo','=',$id],
                ['campus_clinico_seccion.res_encuesta','=','si']])
            ->select(DB::raw('count(campus_clinico_seccion.res_encuesta) as resp_encuesta')
                ,'campus_clinico_seccion.nrc')->get();

             $rotaciones = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre');
                
            })
            ->join('profesor_seccion','campus_clinico_seccion.idProfesor_seccion','profesor_seccion.idProfesor_Campus_clinico')
            ->join('docente','docente.idDocente','=','profesor_seccion.Docente_idDocente')
            ->join('rotacion_por_semestre','campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idRotacion_campus_clinico')
            ->join('campus_decreto','rotacion_por_semestre.Campus_A_Cumplir','=','campus_decreto.idCampus_A_Cumplir')
            ->join('hospital','rotacion_por_semestre.Hospital_idHospital','=','hospital.idHospital')
            ->orderBy('hospital.nombre_hospital')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$id)
            ->groupBy('campus_clinico_seccion.rotacion','seccion_por_semestre.Periodo_idPeriodo','campus_clinico_seccion.nrc','campus_decreto.nombre_asignatura','hospital.nombre_hospital','rotacion_por_semestre.fecha_inicio','rotacion_por_semestre.fecha_termino','rotacion_por_semestre.fecha_inicio_encuesta','docente.nombre')

            ->select(DB::raw('count(campus_clinico_seccion.alumno_seccion) as numero_alumno'),'campus_clinico_seccion.rotacion','seccion_por_semestre.Periodo_idPeriodo','campus_clinico_seccion.nrc','campus_decreto.nombre_asignatura','hospital.nombre_hospital','rotacion_por_semestre.fecha_inicio','rotacion_por_semestre.fecha_termino','rotacion_por_semestre.fecha_inicio_encuesta','docente.nombre')->get();

             $contador_rotaciones = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre');
            })
            ->join('rotacion_por_semestre','campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idRotacion_campus_clinico')
            ->groupBy('rotacion_por_semestre.Campus_A_Cumplir')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$id)
            ->select(DB::raw('count(rotacion_por_semestre.Campus_A_cumplir) as count_campus'),'rotacion_por_semestre.Campus_A_Cumplir')->get();

              $respuestas_rotaciones = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre');
            })
            ->join('profesor_seccion','campus_clinico_seccion.idProfesor_seccion','profesor_seccion.idProfesor_Campus_clinico')
            ->join('docente','docente.idDocente','=','profesor_seccion.Docente_idDocente')
            ->join('rotacion_por_semestre','campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idRotacion_campus_clinico')
            ->join('campus_decreto','rotacion_por_semestre.Campus_A_Cumplir','=','campus_decreto.idCampus_A_Cumplir')
            ->where([['campus_clinico_seccion.res_encuesta','=','si'],
            ['seccion_por_semestre.Periodo_idPeriodo','=',$id]])
            ->groupBy('campus_clinico_seccion.nrc','campus_clinico_seccion.rotacion')
            ->select(DB::raw('count(campus_clinico_seccion.res_encuesta) as resp_encuesta'),'campus_clinico_seccion.nrc','campus_clinico_seccion.rotacion')->get();

             $entregas_rubrica = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre');
            })
            ->join('profesor_seccion','campus_clinico_seccion.idProfesor_seccion','profesor_seccion.idProfesor_Campus_clinico')
            ->join('docente','docente.idDocente','=','profesor_seccion.Docente_idDocente')
            ->join('rotacion_por_semestre','campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idRotacion_campus_clinico')
            ->where([['campus_clinico_seccion.entrego_rubrica','=','si'],
            ['seccion_por_semestre.Periodo_idPeriodo','=',$id]])
            ->groupBy('campus_clinico_seccion.nrc')
            ->select(DB::raw('count(campus_clinico_seccion.entrego_rubrica) as entrego_rubrica'),'campus_clinico_seccion.nrc')->get();

            $rotaciones_rubrica = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre');
            })
            ->join('profesor_seccion','campus_clinico_seccion.idProfesor_seccion','profesor_seccion.idProfesor_Campus_clinico')
            ->join('docente','docente.idDocente','=','profesor_seccion.Docente_idDocente')
            ->join('rotacion_por_semestre','campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idRotacion_campus_clinico')
            ->where([['campus_clinico_seccion.entrego_rubrica','=','si'],
            ['seccion_por_semestre.Periodo_idPeriodo','=',$id]])
            ->groupBy('campus_clinico_seccion.nrc','campus_clinico_seccion.rotacion')
            ->select(DB::raw('count(campus_clinico_seccion.entrego_rubrica) as entrego_rubrica'),'campus_clinico_seccion.nrc','campus_clinico_seccion.rotacion')->get();


            //dd($campus_sa,$num_alumnos_campus_sa,$num_respuestas_campus_sa);
            //dd($rotaciones,$respuestas_rotaciones,$rotaciones_rubrica,$entregas_rubrica);
            
            return view('home',[
            'tipo' => $tipo,
            'periodos' => $periodos,
            'code_periodo' =>$id,
            'asignatura' => $asignatura,
            
            'cantidad_teoricos' => $contador_alumnos,
            
            'contador_alumnos_clinicos' => $contador_alumnos_clinicos,
            'campus_clinico' => $campus_clinico,
            
            'contador_docentes_clinicos' => $contador_docentes_clinicos,
            'rotaciones' => $rotaciones,
            'contador_rotaciones' => $contador_rotaciones,
            'respuestas_teoricas' => $respuestas_teoricas,
            'respuestas_clinicas' => $respuestas_clinicas,
            'respuestas_rotaciones' => $respuestas_rotaciones,
            'entrego_rubrica' => $entregas_rubrica,
            'rotaciones_rubrica' => $rotaciones_rubrica]);
    }

    public function periodo_docente($id)
    {
            $user = Auth::user()->email;
            $tipo = DB::connection('mysql')->table('users')->where('email', $user)->value('tipo');
            $periodos = DB::connection('mysql')->table('periodo')
            ->where('estado','>','1')->get()->toArray();

            //datos para docentes
            $this->rut = DB::connection('mysql')->table('docente')->where('email',$user)->value('idDocente');//rut del docente

            $asignatura = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('asignatura',function($join)
            {
                $join->on('seccion_por_semestre.Asignatura_idAsignatura', '=' , 'asignatura.idAsignatura')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->orderBy('seccion_por_semestre.numero_seccion')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$id)
            ->select('seccion_por_semestre.Periodo_idPeriodo','seccion_por_semestre.numero_seccion','asignatura.Nombre','seccion_por_semestre.actividad','seccion_por_semestre.fecha_inicio_encuesta','seccion_por_semestre.fecha_termino_encuesta')->get();

            //seccion Alumnos Teoricos 
            $contador_alumnos = DB::connection('mysql')->table('seccion_por_semestre')->join('alumno_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion', '=', 'alumno_seccion.nrc')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->groupBy('seccion_por_semestre.actividad','seccion_por_semestre.numero_seccion')
            ->orderBy('seccion_por_semestre.numero_seccion')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$id)
            ->select(DB::raw('count(alumno_seccion.nrc) as cantidad_seccion'),'seccion_por_semestre.numero_seccion','seccion_por_semestre.actividad')->get();
            

            //datos Campus clinico Docente
            $campus_clinico = DB::connection('mysql')->table('rotacion_por_semestre')
           ->join('campus_clinico_seccion','campus_clinico_seccion.seccion_semestre','rotacion_por_semestre.idRotacion_campus_clinico')
            ->join('profesor_seccion',function($join){
                $join->on('profesor_seccion.idProfesor_Campus_clinico','=','campus_clinico_seccion.idProfesor_seccion')
                ->where('profesor_seccion.Docente_idDocente','=',$this->rut);
            })
            ->join('seccion_por_semestre','seccion_por_semestre.idRamo_seccion','campus_clinico_seccion.seccion_semestre')
            ->join('campus_decreto','seccion_por_semestre.Asignatura_idAsignatura','=','campus_decreto.idAsignatura')
            ->orderBy('campus_clinico_seccion.nrc')
            ->select('seccion_por_semestre.Periodo_idPeriodo','campus_clinico_seccion.nrc','campus_decreto.nombre_asignatura')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$id)
            ->distinct()
            ->get();

            //seccion Alumnos campus clinicos
            $contador_alumnos_clinicos = DB::connection('mysql')->table('seccion_por_semestre')
             ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre');
            })
            ->join('alumno_seccion','campus_clinico_seccion.alumno_seccion','=','alumno_seccion.idAlumno_seccion')
            ->join('alumno','alumno.rut','=','alumno_seccion.rut_alumno')
            ->join('profesor_seccion','profesor_seccion.idProfesor_Campus_clinico', 'campus_clinico_seccion.idProfesor_seccion')
            ->groupBy('campus_clinico_seccion.nrc')
            ->where([['seccion_por_semestre.Periodo_idPeriodo','=',$id],
            ['profesor_seccion.Docente_idDocente','=',$this->rut]])
             ->select(DB::raw('count(distinct(alumno.rut)) as cant_alumnos_cli, count(campus_clinico_seccion.res_encuesta) as resp_encuesta')
                ,'campus_clinico_seccion.nrc')->get();

            $contador_docentes_clinicos = DB::connection('mysql')->table('seccion_por_semestre')
             ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre');
            })
            ->join('profesor_seccion','profesor_seccion.idProfesor_Campus_clinico','campus_clinico_seccion.idProfesor_seccion')
            ->groupBy('campus_clinico_seccion.nrc')
            ->where([['seccion_por_semestre.Periodo_idPeriodo','=',$id],
                    ['profesor_seccion.Docente_idDocente','=',$this->rut]])
            ->select(DB::raw('count(distinct(campus_clinico_seccion.idProfesor_seccion)) as cant_profesor'),'campus_clinico_seccion.nrc')->get();

            //seccion rotaciones campus clinico
            $rotaciones = DB::connection('mysql')->table('rotacion_por_semestre')
            ->join('campus_decreto','campus_decreto.idCampus_A_Cumplir','rotacion_por_semestre.Campus_A_Cumplir')
            ->join('campus_clinico_seccion','rotacion_por_semestre.idRotacion_campus_clinico','campus_clinico_seccion.rotacion')
            ->join('profesor_seccion','campus_clinico_seccion.idProfesor_seccion','profesor_seccion.idProfesor_Campus_clinico')
            ->join('docente','docente.idDocente','profesor_seccion.Docente_idDocente')
            ->join('seccion_por_semestre','seccion_por_semestre.idRamo_seccion','campus_clinico_seccion.seccion_semestre')
            ->join('hospital','hospital.idHospital','rotacion_por_semestre.Hospital_idHospital')
            ->where([['profesor_seccion.Docente_idDocente','=',$this->rut],
            ['seccion_por_semestre.Periodo_idPeriodo','=',$id]])
            ->groupBy('campus_clinico_seccion.rotacion','campus_clinico_seccion.nrc','campus_decreto.nombre_asignatura',
            'hospital.nombre_hospital','rotacion_por_semestre.fecha_inicio','rotacion_por_semestre.fecha_termino',
            'rotacion_por_semestre.fecha_inicio_encuesta','docente.nombre')
            ->select(DB::raw('count(campus_clinico_seccion.alumno_seccion) as numero_alumno'),'campus_clinico_seccion.rotacion','campus_clinico_seccion.nrc','campus_decreto.nombre_asignatura',
            'hospital.nombre_hospital','rotacion_por_semestre.fecha_inicio','rotacion_por_semestre.fecha_termino',
            'rotacion_por_semestre.fecha_inicio_encuesta','docente.nombre')->get();

            $contador_rotaciones = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre')
                ->where('seccion_por_semestre.Docente_idDocente','=',$this->rut);
            })
            ->join('rotacion_por_semestre','campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idRotacion_campus_clinico')
            ->groupBy('rotacion_por_semestre.Campus_A_Cumplir')
            ->where('seccion_por_semestre.Periodo_idPeriodo','=',$id)
            ->select(DB::raw('count(rotacion_por_semestre.Campus_A_cumplir) as count_campus'),'rotacion_por_semestre.Campus_A_Cumplir')->get();

            $respuestas_teoricas = DB::connection('mysql')->table('seccion_por_semestre')->join('alumno_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion', '=', 'alumno_seccion.nrc');
                
            })
            ->groupBy('seccion_por_semestre.actividad','seccion_por_semestre.numero_seccion')
            ->orderBy('seccion_por_semestre.numero_seccion')
            ->where([['seccion_por_semestre.Periodo_idPeriodo','=',$id],
            ['alumno_seccion.resp_encuesta','=','si']])
            ->select(DB::raw('count(alumno_seccion.resp_encuesta) as resp_encuesta')
                ,'seccion_por_semestre.numero_seccion','seccion_por_semestre.actividad')->get();

            $respuestas_rotaciones = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre');
            })
            ->join('profesor_seccion','campus_clinico_seccion.idProfesor_seccion','profesor_seccion.idProfesor_Campus_clinico')
            ->join('docente','docente.idDocente','=','profesor_seccion.Docente_idDocente')
            ->join('rotacion_por_semestre','campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idRotacion_campus_clinico')
            ->join('campus_decreto','rotacion_por_semestre.Campus_A_Cumplir','=','campus_decreto.idCampus_A_Cumplir')
            ->where([['campus_clinico_seccion.res_encuesta','=','si'],
            ['seccion_por_semestre.Periodo_idPeriodo','=',$id],
            ['profesor_seccion.Docente_idDocente','=',$this->rut]])
            ->groupBy('campus_clinico_seccion.nrc','campus_clinico_seccion.rotacion')
            ->select(DB::raw('count(campus_clinico_seccion.res_encuesta) as resp_encuesta'),'campus_clinico_seccion.nrc','campus_clinico_seccion.rotacion')->get();

             $entregas_rubrica = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre');
            })
            ->join('profesor_seccion','campus_clinico_seccion.idProfesor_seccion','profesor_seccion.idProfesor_Campus_clinico')
            ->join('docente','docente.idDocente','=','profesor_seccion.Docente_idDocente')
            ->join('rotacion_por_semestre','campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idRotacion_campus_clinico')
            ->where([['campus_clinico_seccion.entrego_rubrica','=','si'],
            ['seccion_por_semestre.Periodo_idPeriodo','=',$id],
            ['profesor_seccion.Docente_idDocente','=',$this->rut]])
            ->groupBy('campus_clinico_seccion.nrc')
            ->select(DB::raw('count(campus_clinico_seccion.entrego_rubrica) as entrego_rubrica'),'campus_clinico_seccion.nrc')->get();

            $rotaciones_rubrica = DB::connection('mysql')->table('seccion_por_semestre')
            ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre');
            })
            ->join('profesor_seccion','campus_clinico_seccion.idProfesor_seccion','profesor_seccion.idProfesor_Campus_clinico')
            ->join('docente','docente.idDocente','=','profesor_seccion.Docente_idDocente')
            ->join('rotacion_por_semestre','campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idRotacion_campus_clinico')
            ->join('campus_decreto','rotacion_por_semestre.Campus_A_Cumplir','=','campus_decreto.idCampus_A_Cumplir')
            ->where([['campus_clinico_seccion.res_encuesta','=','si'],
            ['seccion_por_semestre.Periodo_idPeriodo','=',$id],
            ['profesor_seccion.Docente_idDocente','=',$this->rut]])
            ->groupBy('campus_clinico_seccion.nrc','campus_clinico_seccion.rotacion')
            ->select(DB::raw('count(campus_clinico_seccion.entrego_rubrica) as entrego_rubrica'),'campus_clinico_seccion.nrc','campus_clinico_seccion.rotacion')->get();

             $respuestas_clinicas = DB::connection('mysql')->table('seccion_por_semestre')
             ->join('campus_clinico_seccion',function($join)
            {
                $join->on('seccion_por_semestre.idRamo_seccion','=','campus_clinico_seccion.seccion_semestre');
               
            })
            ->join('alumno_seccion','campus_clinico_seccion.alumno_seccion','=','alumno_seccion.idAlumno_seccion')
            ->join('alumno','alumno.rut','=','alumno_seccion.rut_alumno')
            ->join('profesor_seccion','profesor_seccion.idProfesor_Campus_clinico','campus_clinico_seccion.idProfesor_seccion')
            ->groupBy('campus_clinico_seccion.nrc')
            ->where([['seccion_por_semestre.Periodo_idPeriodo','=',$id],
                ['campus_clinico_seccion.res_encuesta','=','si'],
                ['profesor_seccion.Docente_idDocente','=',$this->rut]])
            ->select(DB::raw('count(campus_clinico_seccion.res_encuesta) as resp_encuesta')
                ,'campus_clinico_seccion.nrc')->get();
            
        //dd($contador_alumnos_clinicos);
        //dd($campus_clinico,$alumnos_clinicos,$docentes_clinicos,$contador_rotaciones,$rotaciones);
        //dd($docentes_clinicos,$contador_rotaciones,$rotaciones);
        //dd($contador_alumnos_clinicos,$contador_docentes_clinicos);
        //dd($alumnos_teoricos,$asignatura->isEmpty(),$contador_alumnos);
        //dd($contador_alumnos_clinicos,$respuestas_clinicas,$respuestas_rotaciones,$entregas_rubrica,$rotaciones_rubrica);

     return view('home',[
            'tipo' => $tipo,
            'periodos' => $periodos,
            'code_periodo' =>$id,
            'asignatura' => $asignatura,
            'cantidad_teoricos' => $contador_alumnos,
            'contador_alumnos_clinicos' => $contador_alumnos_clinicos,
            'campus_clinico' => $campus_clinico,
            'contador_docentes_clinicos' => $contador_docentes_clinicos,
            'rotaciones' => $rotaciones,
            'contador_rotaciones' => $contador_rotaciones,
            'respuestas_teoricas' => $respuestas_teoricas,
            'respuestas_clinicas' => $respuestas_clinicas,
            'respuestas_rotaciones' => $respuestas_rotaciones,
            'entrego_rubrica' => $entregas_rubrica,
            'rotaciones_rubrica' => $rotaciones_rubrica]); 
        
    }

    public function encuesta(Request $request)
    {
        define( 'LS_BASEURL', 'http://limesurvey.test/index.php');  // adjust this one to your actual LimeSurvey URL
        define( 'LS_USER', 'Alberto' );
        define( 'LS_PASSWORD', 'oviedo83' );

        $this->rut = DB::connection('mysql')->table('alumno')->where('email',Auth::user()->email)->value('rut');

        $this->rut = substr((string)$this->rut,0,4);
        // the survey to process
        $url = $request->query('url');

        $survey_id= Str::before($url,'?');
        $survey_id = Str::after($survey_id,'http://limesurvey.test/index.php/');

        // instantiate a new client
        $myJSONRPCClient = new \org\jsonrpcphp\JsonRPCClient( LS_BASEURL.'/admin/remotecontrol' );

        // receive session key
        $sessionKey= $myJSONRPCClient->get_session_key( LS_USER, LS_PASSWORD );

        // receive surveys list current user can read
        $groups = $myJSONRPCClient->list_surveys( $sessionKey );

        $users = [array('email' => Auth::user()->email, 'token' => $this->rut)];

        $attributes = ["completed","usesleft"];

        $adding_participants = $myJSONRPCClient->add_participants($sessionKey,$survey_id,$users,false);

        //dd($groups,$users,$url,$survey_id,$adding_participants,$this->rut);
        // release the session key
        $myJSONRPCClient->release_session_key( $sessionKey );

        return Redirect::to($url);
    }
}

