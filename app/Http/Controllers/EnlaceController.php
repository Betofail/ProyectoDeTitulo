<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class EnlaceController extends Controller
{

    public function index()
    {
        define('LS_BASEURL', 'http://limesurvey.test/index.php');  // adjust this one to your actual LimeSurvey URL
        define('LS_USER', 'Alberto');
        define('LS_PASSWORD', 'master12');

        // instantiate a new client
        $myJSONRPCClient = new \org\jsonrpcphp\JsonRPCClient(LS_BASEURL . '/admin/remotecontrol');

        // receive session key
        $sessionKey = $myJSONRPCClient->get_session_key(LS_USER, LS_PASSWORD);

        $list_surveys = $myJSONRPCClient->list_surveys($sessionKey);

        $myJSONRPCClient->release_session_key($sessionKey);

        $asignaturas = DB::connection('mysql3')->table('seccion_semestres')
            ->join('asignaturas', 'asignaturas.idAsignatura', '=', 'seccion_semestres.nrc')
            ->join('docentes', 'docentes.rut', '=', 'seccion_semestres.idDocente')
            ->join('mallas',function($join){
                $join->on('mallas.CodAsign','=','asignaturas.codigo_asignatura')
                ->where([['mallas.Encuesta','=',1]]);
            })
            ->select('asignaturas.nombre as asign', 'seccion_semestres.nrc', 'docentes.nombre', 'seccion_semestres.actividad')
            ->where([['asignaturas.Liga', '=', '']])->distinct()->get();

        $asignaturas_con_encuestas = DB::connection('mysql3')->table('seccion_semestres')
            ->join('asignaturas', 'asignaturas.idAsignatura', '=', 'seccion_semestres.nrc')
            ->join('docentes', 'docentes.rut', '=', 'seccion_semestres.idDocente')
            ->join('mallas',function($join){
                $join->on('mallas.CodAsign','=','asignaturas.codigo_asignatura')
                ->where([['mallas.Encuesta','=',1]]);
            })
            ->select('asignaturas.nombre as asign', 'seccion_semestres.nrc', 'docentes.nombre', 'seccion_semestres.actividad')
            ->where([['seccion_semestres.link_encuesta', '=', 'limesurvey.test/index.php/' . $list_surveys[0]['sid']], ['asignaturas.Liga', '=', '']])->distinct()->get();

        return view('enlace', [
            'encuestas' => $list_surveys, 'asignaturas' => $asignaturas, 'sid_en' => $list_surveys[0]['sid'],
            'nombre_en' => $list_surveys[0]['surveyls_title'], 'asignaturas_con' => $asignaturas_con_encuestas
        ]);
    }

    public function tipo_encuesta($id)
    {
        define('LS_BASEURL', 'http://limesurvey.test/index.php');  // adjust this one to your actual LimeSurvey URL
        define('LS_USER', 'Alberto');
        define('LS_PASSWORD', 'master12');

        // instantiate a new client
        $myJSONRPCClient = new \org\jsonrpcphp\JsonRPCClient(LS_BASEURL . '/admin/remotecontrol');

        // receive session key
        $sessionKey = $myJSONRPCClient->get_session_key(LS_USER, LS_PASSWORD);

        $list_surveys = $myJSONRPCClient->list_surveys($sessionKey);
        foreach ($list_surveys as $key => $value) {
            if (array_search($id, $value)) {
                $nombre_encuesta = $value['surveyls_title'];
                break;
            }
        }

        $myJSONRPCClient->release_session_key($sessionKey);

        $asignaturas = DB::connection('mysql3')->table('seccion_semestres')
            ->join('asignaturas', 'asignaturas.idAsignatura', '=', 'seccion_semestres.nrc')
            ->join('docentes', 'docentes.rut', '=', 'seccion_semestres.idDocente')
            ->join('mallas',function($join){
                $join->on('mallas.CodAsign','=','asignaturas.codigo_asignatura')
                ->where([['mallas.Encuesta','=',1]]);
            })
            ->select('asignaturas.nombre as asign', 'seccion_semestres.nrc', 'docentes.nombre', 'seccion_semestres.actividad')
            ->where([['asignaturas.Liga', '=', '']])->distinct()->get();

        $asignaturas_con_encuestas = DB::connection('mysql3')->table('seccion_semestres')
            ->join('asignaturas', 'asignaturas.idAsignatura', '=', 'seccion_semestres.nrc')
            ->join('docentes', 'docentes.rut', '=', 'seccion_semestres.idDocente')
            ->join('mallas',function($join){
                $join->on('mallas.CodAsign','=','asignaturas.codigo_asignatura')
                ->where([['mallas.Encuesta','=',1]]);
            })
            ->select('asignaturas.nombre as asign', 'seccion_semestres.nrc', 'docentes.nombre', 'seccion_semestres.actividad')
            ->where([['seccion_semestres.link_encuesta', '=', 'limesurvey.test/index.php/' . $id], ['asignaturas.Liga', '=', '']])->distinct()->get();

        return view('enlace', [
            'encuestas' => $list_surveys, 'asignaturas' => $asignaturas, 'sid_en' => $id,
            'nombre_en' => $nombre_encuesta, 'asignaturas_con' => $asignaturas_con_encuestas
        ]);
    }
    public function index_cli()
    {
        define('LS_BASEURL', 'http://limesurvey.test/index.php');  // adjust this one to your actual LimeSurvey URL
        define('LS_USER', 'Alberto');
        define('LS_PASSWORD', 'master12');

        // instantiate a new client
        $myJSONRPCClient = new \org\jsonrpcphp\JsonRPCClient(LS_BASEURL . '/admin/remotecontrol');

        // receive session key
        $sessionKey = $myJSONRPCClient->get_session_key(LS_USER, LS_PASSWORD);

        $list_surveys = $myJSONRPCClient->list_surveys($sessionKey);

        $myJSONRPCClient->release_session_key($sessionKey);

        $asignaturas = DB::connection('mysql3')->table('seccion_semestres')
            ->join('asignaturas', 'asignaturas.idAsignatura', '=', 'seccion_semestres.nrc')
            ->join('docentes', 'docentes.rut', '=', 'seccion_semestres.idDocente')
            ->join('mallas',function($join){
                $join->on('mallas.CodAsign','=','asignaturas.codigo_asignatura')
                ->where([['mallas.CampusClinico','=',1],['asignaturas.Liga','!=','']]);
            })
            ->orderBy('seccion_semestres.actividad')
            ->select('asignaturas.nombre as asign', 'seccion_semestres.nrc', 'docentes.nombre', 'seccion_semestres.actividad')
            ->where([['seccion_semestres.link_encuesta', '=', 'none']])->distinct()->get();

        $asignaturas_con_encuestas = DB::connection('mysql3')->table('seccion_semestres')
            ->join('asignaturas', 'asignaturas.idAsignatura', '=', 'seccion_semestres.nrc')
            ->join('docentes', 'docentes.rut', '=', 'seccion_semestres.idDocente')
            ->join('mallas',function($join){
                $join->on('mallas.CodAsign','=','asignaturas.codigo_asignatura')
                ->where([['mallas.CampusClinico','=',1],['asignaturas.Liga','!=','']]);
            })
            ->orderBy('seccion_semestres.actividad')
            ->select('asignaturas.nombre as asign', 'seccion_semestres.nrc', 'docentes.nombre', 'seccion_semestres.actividad')
            ->where([['seccion_semestres.link_encuesta', '=', 'limesurvey.test/index.php/' . $list_surveys[0]['sid']]])->distinct()->get();

        return view('enlace_cli', [
            'encuestas' => $list_surveys, 'asignaturas' => $asignaturas, 'sid_en' => $list_surveys[0]['sid'],
            'nombre_en' => $list_surveys[0]['surveyls_title'], 'asignaturas_con' => $asignaturas_con_encuestas
        ]);
    }

    public function tipo_encuesta_cli($id)
    {
        define('LS_BASEURL', 'http://limesurvey.test/index.php');  // adjust this one to your actual LimeSurvey URL
        define('LS_USER', 'Alberto');
        define('LS_PASSWORD', 'master12');

        // instantiate a new client
        $myJSONRPCClient = new \org\jsonrpcphp\JsonRPCClient(LS_BASEURL . '/admin/remotecontrol');

        // receive session key
        $sessionKey = $myJSONRPCClient->get_session_key(LS_USER, LS_PASSWORD);

        $list_surveys = $myJSONRPCClient->list_surveys($sessionKey);
        foreach ($list_surveys as $key => $value) {
            if (array_search($id, $value)) {
                $nombre_encuesta = $value['surveyls_title'];
                break;
            }
        }

        $myJSONRPCClient->release_session_key($sessionKey);

        $asignaturas = DB::connection('mysql3')->table('seccion_semestres')
        ->join('asignaturas', 'asignaturas.idAsignatura', '=', 'seccion_semestres.nrc')
        ->join('docentes', 'docentes.rut', '=', 'seccion_semestres.idDocente')
        ->join('mallas',function($join){
            $join->on('mallas.CodAsign','=','asignaturas.codigo_asignatura')
            ->where([['mallas.CampusClinico','=',1],['asignaturas.Liga','!=','']]);
        })->orderBy('seccion_semestres.actividad')
        ->select('asignaturas.nombre as asign', 'seccion_semestres.nrc', 'docentes.nombre', 'seccion_semestres.actividad')
        ->where([['seccion_semestres.link_encuesta', '=', 'none']])->distinct()->get();

    $asignaturas_con_encuestas = DB::connection('mysql3')->table('seccion_semestres')
        ->join('asignaturas', 'asignaturas.idAsignatura', '=', 'seccion_semestres.nrc')
        ->join('docentes', 'docentes.rut', '=', 'seccion_semestres.idDocente')
        ->join('mallas',function($join){
            $join->on('mallas.CodAsign','=','asignaturas.codigo_asignatura')
            ->where([['mallas.CampusClinico','=',1],['asignaturas.Liga','!=','']]);
        })->orderBy('seccion_semestres.actividad')
        ->select('asignaturas.nombre as asign', 'seccion_semestres.nrc', 'docentes.nombre', 'seccion_semestres.actividad')
        ->where([['seccion_semestres.link_encuesta', '=', 'limesurvey.test/index.php/' . $id]])->distinct()->get();

        return view('enlace_cli', [
            'encuestas' => $list_surveys, 'asignaturas' => $asignaturas, 'sid_en' => $id,
            'nombre_en' => $nombre_encuesta, 'asignaturas_con' => $asignaturas_con_encuestas
        ]);
    }

    public function enlasar_encuesta(Request $request)
    {
        define('LS_BASEURL', 'http://limesurvey.test/index.php');  // adjust this one to your actual LimeSurvey URL
        define('LS_USER', 'Alberto');
        define('LS_PASSWORD', 'master12');

        // instantiate a new client
        $myJSONRPCClient = new \org\jsonrpcphp\JsonRPCClient(LS_BASEURL . '/admin/remotecontrol');
        // receive session key
        $sessionKey = $myJSONRPCClient->get_session_key(LS_USER, LS_PASSWORD);

        $list_surveys = $myJSONRPCClient->list_surveys($sessionKey);
        $aAttributes = ['completed'];
        $list_participants = $myJSONRPCClient->list_participants($sessionKey, 21313221, $aAttributes);

        $myJSONRPCClient->release_session_key($sessionKey);

        $lista = $request->input();
        array_shift($lista);
        $sid = array_shift($lista);
        if (empty($lista)) {
            return back()->with('error', 'realize algun cambio');
        }

        foreach ($lista as $key => $value) {
            $encuesta = explode('/', $key);
            $encuesta = $encuesta[0];

            if ($encuesta == 'con_en') {
                $asignatura = explode('*', $value);

                $nrc_profesor = explode('/', $asignatura[1]);
                $docente = DB::connection('mysql3')->table('docentes')->select('rut')->where('nombre', '=', trim($nrc_profesor[1]))->get();
                $periodo = DB::connection('mysql3')->table('asignaturas')->select('semestre','actividad')
                ->where('idAsignatura','=',trim($nrc_profesor[0]))->first();
                $seccion = DB::connection('mysql3')->table('seccion_semestres')->select('link_encuesta','idPeriodo','actividad','idDocente','nrc')->where([
                    'nrc' => trim($nrc_profesor[0]),
                    'idDocente' => $docente[0]->rut,
                    'link_encuesta' => "limesurvey.test/index.php/". $sid
                ])->get();

                if(empty($seccion[0])){

                    $seccion = DB::connection('mysql3')->table('seccion_semestres')->select('link_encuesta','idPeriodo','actividad','idDocente','nrc')->where([
                        'nrc' => trim($nrc_profesor[0]),
                        'idDocente' => $docente[0]->rut,
                        'link_encuesta' => "none"
                    ])->get();

                    if(!empty($seccion[0])){

                        foreach ($list_surveys as $key => $value) {
                            if ($value["sid"] == $sid) {
                                DB::connection('mysql3')->table('seccion_semestres')->where([
                                    'idPeriodo' =>$periodo->semestre,
                                    'nrc' => $seccion[0]->nrc,
                                    'idDocente' => $docente[0]->rut
                                ])->update([
                                    'link_encuesta' => 'limesurvey.test/index.php/' . $sid, 'fecha_inicio_encuesta' => $value['startdate'],
                                    'fecha_termino_encuesta' => $value['expires']
                                ]);
                            } else {
                                continue;
                            }
                        }
                    }else{
                        foreach ($list_surveys as $key => $value) {
                            if ($value["sid"] == $sid) {
                                DB::connection('mysql3')->table('seccion_semestres')
                                ->insert([
                                    ['nrc' => trim($nrc_profesor[0]),'idDocente' => $docente[0]->rut,'idPeriodo' => $periodo->semestre,
                                    'link_encuesta' => 'limesurvey.test/index.php/' . $sid, 'fecha_inicio_encuesta' => $value['startdate'],
                                    'fecha_termino_encuesta' => $value['expires'],'actividad' => trim($nrc_profesor[2])]
                                ]);
                            } else {
                                continue;
                            }
                        }
                    }
                }
            } elseif ($encuesta == 'sin_en') {
                $asignatura = explode('*', $value);
                $nrc_profesor = explode('/', $asignatura[1]);
                $docente = DB::connection('mysql3')->table('docentes')->select('rut')->where('nombre', '=', trim($nrc_profesor[1]))->get();
                $seccion = DB::connection('mysql3')->table('seccion_semestres')->select('idSeccion','link_encuesta')->where([
                    'nrc' => trim($nrc_profesor[0]),
                    'idDocente' => $docente[0]->rut,
                    'link_encuesta' => 'limesurvey.test/index.php/' . $sid
                ])->get();
                $registro = DB::connection('mysql3')->table('seccion_semestres')->selectRaw('count(idSeccion) as contador')->where([
                    'nrc' => trim($nrc_profesor[0]),
                    'idDocente' => $docente[0]->rut
                ])->first();

                if ($seccion[0]->link_encuesta != 'none') {
                    $myJSONRPCClient = new \org\jsonrpcphp\JsonRPCClient(LS_BASEURL . '/admin/remotecontrol');
                    $sessionKey = $myJSONRPCClient->get_session_key(LS_USER, LS_PASSWORD);
                    $list_participants = $myJSONRPCClient->list_participants($sessionKey, $sid);
                    $myJSONRPCClient->release_session_key($sessionKey);
                    foreach ($list_participants as $key => $value) {

                        if ($value == "No survey participants found.") {
                            if($registro->contador == 1){
                                DB::connection('mysql3')->table('seccion_semestres')->where([
                                    'idSeccion' => $seccion[0]->idSeccion
                                ])->update([
                                    'link_encuesta' => 'none', 'fecha_inicio_encuesta' => null,
                                    'fecha_termino_encuesta' => null
                                ]);
                            }else{
                                DB::connection('mysql3')->table('seccion_semestres')->where([
                                    'idSeccion' => $seccion[0]->idSeccion
                                ])->delete();
                            }
                        } elseif ($value == "Error: Invalid survey ID") {
                            return back()->with('error', 'la encuesta no existe');
                        } else {
                            return back()->with('error', 'la encuesta ya tiene particiapantes');
                        }
                    }
                }
            }
        }
        return back()->with('success', 'se realizaron los cambios correctamente');
    }
}
