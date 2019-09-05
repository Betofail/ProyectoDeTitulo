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

Route::get('/', function () {
    return view('auth/login');
});

Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

Route::get('/home/PA/{id}','HomeController@periodo_pa')->name('periodo_pa');

Route::get('/home/Sa/{id}','HomeController@peridos_sa')->name('periodo_sa');

Route::get('/home/docente/{id}','HomeController@periodo_docente')->name('periodo_doc');

Route::get('/home/alumno/','HomeController@encuesta')->name('fun_encuesta');

Route::get('/notification','NotificationController@index')->name('notificacion');

Route::post('/notification','NotificationController@enviar')->name('notification.enviar');