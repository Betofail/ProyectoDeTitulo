<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth/login');
});

Auth::routes();

Route::get('/home', 'home2@index')->name('home');

Route::get('/home/PA/{id}','home2@periodos_PA')->name('periodo_pa');

Route::get('/home/SA/periodo/{id}','home2@periodos_SA')->name('periodo_sa');
Route::get('/home/SA/carrera/{id}','home2@carreras_SA')->name('carrera_sa');

Route::get('/home/OFEM/carrera/{id}','home2@carreras_SA')->name('carrera_ofem');

Route::get('/home/docente/{id}','home2@periodo_docente')->name('periodo_doc');

Route::get('/home/alumno/','home2@encuesta')->name('fun_encuesta');

Route::get('/notification','NotificationController@index')->name('notificacion');

Route::post('/notification','NotificationController@enviar')->name('notification.enviar');

Route::post('/home','NotificationController@encuestas')->name('encuestas');

Route::get('/enlace','EnlaceController@index')->name('enlace');
Route::get('/encuesta_enlace/{id}','EnlaceController@tipo_encuesta')->name('cambio_encuesta');

Route::get('/enlace_cli','EnlaceController@index_cli')->name('enlace_cli');
Route::get('/encuesta_enlace_cli/{id}','EnlaceController@tipo_encuesta_cli')->name('cambio_encuesta_cli');

Route::get('/asignar','AsignarController@index')->name('asignar');
Route::get('/asignar/periodo/{id}','AsignarController@cambio_periodo')->name('cambio_periodo');
Route::post('/asignar/enlasado','AsignarController@asignar_asignatura')->name('asignar_asignatura');

Route::get('/asignar_cli','AsignarController@index_cli')->name('asignar_cli');
Route::get('/asignar_cli/periodo/{id}','AsignarController@cambio_periodo_cli')->name('cambio_periodo_cli');
Route::get('/asignar_cli/enlasado','AsignarController@asignar_asignatura_cli')->name('asignar_asignatura_cli');

Route::post('change_enc','EnlaceController@enlasar_encuesta')->name('enlasar_encuestas');

Route::get('/cargador','CargadorController@index')->name('cargador');
Route::get('export','CargadorController@export')->name('export');
Route::post('import_alu','CargadorController@import')->name('import_alu');
Route::post('import_sec','CargadorController@import_secction')->name('import_sec');
Route::post('import_asing','CargadorController@import_asignatura')->name('import_asing');
Route::post('import_docente','CargadorController@import_docente')->name('import_docente');
Route::post('import_malla','CargadorController@import_malla')->name('import_malla');
