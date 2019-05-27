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
        $tipo = DB::connection('mysql')->table('tipos_cuentas')->where('email', $user)->value('tipo');
    
        if ($tipo == 'alumno') {

            $this->rut = DB::connection('mysql')->table('alumno')->where('email',$user)->value('rut');
            $asignatura = DB::connection('mysql')->table('alumno_seccion')
            ->join('seccion_por_semestre',function($join)
            {
                $join->on('alumno_seccion.nrc','=','seccion_por_semestre.idRamo_seccion')
                ->where('alumno_seccion.rut_alumno','=',$this->rut);
            })
            ->join('docente', 'seccion_por_semestre.Docente_idDocente','=','docente.idDocente')
            ->join('asignatura','seccion_por_semestre.Asignatura_idAsignatura', '=', 'asignatura.idAsignatura')
            ->select('seccion_por_semestre.link_encuesta','asignatura.Nombre', 'docente.nombre','asignatura.Semestre')->get();

            $campus_clinico =DB::connection('mysql')->table('alumno_seccion')
            ->join('seccion_por_semestre',function($join)
            {
                $join->on('alumno_seccion.nrc','=','seccion_por_semestre.idRamo_seccion')
                ->where('alumno_seccion.rut_alumno','=',$this->rut);
            })
            ->join('asignatura','seccion_por_semestre.Asignatura_idAsignatura','=','asignatura.idAsignatura')
            ->join('campus_clinico_seccion','alumno_seccion.idAlumno_seccion','=','campus_clinico_seccion.alumno_seccion')
            ->join('rotacion_por_semestre', 'campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idCampus_Clinico')
            ->join('hospital','rotacion_por_semestre.Hospital_idHospital','=','hospital.idHospital')
            ->select('asignatura.nombre','hospital.nombre_hospital','rotacion_por_semestre.fecha_inicio','rotacion_por_semestre.fecha_termino','seccion_por_semestre.link_encuesta')->get();

            //dd($asignatura,$campus_clinico);

        }else{

            $this->rut = DB::connection('mysql')->table('docente')->where('email',$user)->value('idDocente');
            $asignatura = DB::connection('mysql')->table('profesor_seccion')
            ->join('seccion_por_semestre',function($join)
            {
                $join->on('profesor_seccion.Docente_idDocente','=','seccion_por_semestre.Docente_idDocente')
                ->where('profesor_seccion.Docente_idDocente','=',$this->rut);
            })
            ->join('asignatura','seccion_por_semestre.Asignatura_idAsignatura', '=' , 'asignatura.idAsignatura')
            ->join('docente', 'profesor_seccion.Docente_idDocente','=','docente.idDocente')
            ->join('campus_decreto', 'asignatura.idAsignatura','=','campus_decreto.idAsignatura')
            ->select('seccion_por_semestre.link_encuesta', 'asignatura.Nombre', 'asignatura.Semestre', 'docente.nombre')->get();

            $campus_clinico = DB::connection('mysql')->table('profesor_seccion')
            ->join('seccion_por_semestre',function($join)
            {
                $join->on('profesor_seccion.Docente_idDocente','=','seccion_por_semestre.Docente_idDocente')
                ->where('profesor_seccion.Docente_idDocente','=',$this->rut);
            })
            ->join('asignatura','seccion_por_semestre.Asignatura_idAsignatura', '=' , 'asignatura.idAsignatura')
            ->join('docente', 'profesor_seccion.Docente_idDocente','=','docente.idDocente')
            ->join('campus_clinico_seccion','campus_clinico_seccion.profesor_seccion','=','profesor_seccion.idProfesor_Campus_clinico')
            ->join('rotacion_por_semestre','campus_clinico_seccion.rotacion','=','rotacion_por_semestre.idCampus_Clinico')
            ->join('hospital','rotacion_por_semestre.Hospital_idHospital','=','hospital.idHospital')
            ->select('asignatura.nombre','seccion_por_semestre.link_encuesta','rotacion_por_semestre.fecha_inicio','rotacion_por_semestre.fecha_termino','hospital.nombre_hospital')
            ->get();

            //dd($asignatura,$campus_clinico);

        }

        return view('home',['asignatura' => $asignatura,'campus_clinico' => $campus_clinico]);
    }
}
