@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col">
            <div class="card">
                <div class="card-header">Asignaturas Teoricas</div>

                <div class="card-body">
                    @if(is_null($asignatura))
                        <h1>No Tiene Asignaturas Inscritas</h1>
                    @else
                    <table class="table table-hover" width="100%" cellspacing="0">
                        <thead>
                            <th>Periodo</th>
                            <th>NRC Asignatura</th>
                            <th>Asignatura</th>
                            <th>Inicio Encuesta</th>
                            <th>Termino Encuesta</th>
                            <th>Alumnos</th>
                            <th>Actividad</th>
                        </thead>
                        <tbody>
                        @foreach($asignatura as $key => $value)
                        <tr>
                            <td>{{$value->Periodo_idPeriodo}}</td>
                            <td>{{$value->numero_seccion}}</td>
                            <td>{{$value->Nombre}}</td>
                            <td>{{$value->fecha_inicio_encuesta}}</td>
                            <td>{{$value->fecha_termino_encuesta}}</td>
                            <td>{{$value->actividad}}</td>
                            @if($cantidad_teoricos[$key]->numero_seccion == $value->numero_seccion 
                                && $cantidad_teoricos[$key]->actividad == $asignatura[$key]->actividad)
                            <td><Button type="submit" class="btn btn-lg" data-toggle="modal" data-target="#modal{{$key}}">
                                <i class="fa fa-address-book" style="font-size: 16px" aria-hidden="true"></i>
                                <span>{{$cantidad_teoricos[$key]->cantidad_seccion}}</span></Button></td>
                            @else
                            <td><p>no tiene</p></td>
                            @endif

                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
            </div>
        </div>

    @foreach($asignatura as $key => $value)    
    <!-- Modal Alumnos Teoricos-->
        <div id="modal{{$key}}" class="modal fade" role="dialog">
            <div class="modal-dialog">
                <!-- Modal content-->
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">Alumnos</h4>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                    <div class="modal-body">
                        <table class="table table-responsive">
                            <thead>
                                <th>Rut</th>
                                <th>Nombre Completo</th>
                                <th>Encuesta S/N</th>
                            </thead>
                            <tbody>
                                @foreach($alumnos_teoricos as $key_alum => $value_alum)
                                @if($value_alum->numero_seccion == $value->numero_seccion && $value_alum->actividad == $value->actividad)
                                    <tr>
                                        <td>{{$value_alum->rut}}</td>
                                        <td>{{$value_alum->nombre}}</td>
                                        <td>{{$value_alum->resp_encuesta}}</td>
                                    </tr>
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                    </div>
            </div>
        </div>
    @endforeach

        <div class="col mt-4">
            <div class="card">
                <div class="card-header">Campus Clinico Vigentes</div>

                <div class="card-body">
                    @if(is_null($campus_clinico))
                        <h3>No tiene Campus Clinicos Inscritos</h3>
                    @else
                    <table class="table table-responsive">
                        <thead>
                            <th>Periodo</th>
                            <th>NRC</th>
                            <th>Nombre Asignatura</th>
                            <th>Alumnos</th>
                            <th>Docentes</th>
                            <th>Rotaciones</th>
                        </thead>
                        <tbody>
                            @foreach($campus_clinico as $key => $value)
                            <tr>
                                <td>{{$value->Periodo_idPeriodo}}</td>
                                <td>{{$value->nrc}}</td>
                                <td>{{$value->nombre_asignatura}}</td>
                               
                                <td><Button type="submit" class="btn btn-lg" data-toggle="modal" data-target="#campus_alu{{$key}}">
                                <i class="fa fa-address-book" style="font-size: 16px" aria-hidden="true"></i>
                                <span>{{$contador_alumnos_clinicos[$key]->cant_alumnos_cli}}</span></Button></td>
                                <td><Button type="submit" class="btn btn-lg" data-toggle="modal" data-target="#campus_doc{{$key}}">
                                <i class="fa fa-users" style="font-size: 16px" aria-hidden="true"></i>
                                <span>{{$contador_docentes_clinicos[$key]->cant_profesor}}</span></Button></td>
                                <td data-toggle="collapse" data-target="#acrodion{{$key}}" class="accordion-toggle"><button class="btn btn-default"><i class="fas fa-arrow-circle-down"></i></button></td>
                            </tr>
                            <tr>
                                <td colspan="5" class="hiddenRow">
                                    <div class="table-responsive collapse" id="acrodion{{$key}}">
                                        <table class="table table-striped">
                                        <thead>
                                            <tr>
                                            <th>Periodo</th>
                                            <th>NRC</th>
                                            <th>N° Rotacion</th>
                                            <th>Asignatura</th>
                                            <th>Hospital</th>
                                            <th>Fecha inicio</th>
                                            <th>Fecha termino</th>
                                            <th>Fecha inicio encuesta</th>
                                            <th>docente a cargo</th>
                                            <th>N° Alumnos</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($rotaciones as $key_rot => $value_rot)
                                            @if($value_rot->nrc == $value->nrc)
                                            <tr>
                                                <td>{{$value_rot->Periodo_idPeriodo}}</td>
                                                <td>{{$value_rot->nrc}}</td>
                                                <td>{{$key_rot + 1}}</td>
                                                <td>{{$value_rot->nombre_asignatura}}</td>
                                                <td>{{$value_rot->nombre_hospital}}</td>
                                                <td>{{$value_rot->fecha_inicio}}</td>
                                                <td>{{$value_rot->fecha_termino}}</td>
                                                <td>{{$value_rot->fecha_inicio_encuesta}}</td>
                                                <td>{{$value_rot->nombre}}</td>
                                                <td>{{$value_rot->numero_alumno}}</td>
                                            </tr>
                                            @endif
                                            @endforeach
                                        </tbody>
                                    </table>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
            </div>
        </div>
    
    @foreach($campus_clinico as $key => $value)    
    <!-- Modal Campus Clinico Alumnos -->
        <div id="campus_alu{{$key}}" class="modal fade" role="dialog">
            <div class="modal-dialog modal-sm">
                <!-- Modal content-->
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">Alumnos</h4>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                    <div class="modal-body">
                        <table class="table table-responsive">
                            <thead>
                                <th>Rut</th>
                                <th>Nombre Completo</th>
                            </thead>
                            <tbody>
                                @foreach($alumnos_clinicos as $key_alum => $value_alum)
                                @if($value_alum->nrc == $value->nrc)
                                    <tr>
                                        <td>{{$value_alum->rut}}</td>
                                        <td>{{$value_alum->nombre}}</td>
                                    </tr>
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                    </div>
            </div>
        </div>
    @endforeach

    @foreach($campus_clinico as $key => $value)    
    <!-- Modal Campus Clinico Docentes -->
        <div id="campus_doc{{$key}}" class="modal fade" role="dialog">
            <div class="modal-dialog modal-sm">
                <!-- Modal content-->
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">Docente</h4>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                    <div class="modal-body">
                        <table class="table table-responsive">
                            <thead>
                                <th>Rut</th>
                                <th>Nombre Completo</th>
                            </thead>
                            <tbody>
                                @foreach($docentes_clinicos as $key_doc => $value_doc)
                                @if($value_doc->nrc == $value->nrc)
                                    <tr>
                                        <td>{{$value_doc->idDocente}}</td>
                                        <td>{{$value_doc->nombre}}</td>
                                        
                                    </tr>
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                    </div>
            </div>
        </div>
    @endforeach

    @foreach($campus_clinico as $key => $value)    
    <!-- Modal Campus Clinico Rotaciones -->
        <div id="rotaciones{{$key}}" class="modal fade" role="dialog">
            <div class="modal-dialog modal-lg">
                <!-- Modal content-->
                    <div class="modal-content">
                        <div class="modal-header">
                            <h4 class="modal-title">Rotaciones</h4>
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                        </div>
                    <div class="modal-body">
                        <table class="table table-responsive">
                            <thead>
                                <th>Periodo</th>
                                <th>NRC</th>
                                <th>N° Rotacion</th>
                                <th>Asignatura</th>
                                <th>Hospital</th>
                                <th>Fecha Inicio Rotacion</th>
                                <th>Fecha Termino Rotacion</th>
                                <th>Fecha Inicio Encuesta</th>
                                <th>Profesor Rotacion</th>
                            </thead>
                            <tbody>
                                @foreach($rotaciones as $key_rotaciones => $value_rot)
                                @if($value_rot->nrc == $value->nrc)
                                    <tr>
                                        <td>{{$value_rot->Periodo_idPeriodo}}</td>
                                        <td>{{$value_rot->nrc}}</td>
                                        <td>{{$key_rotaciones + 1}}</td>
                                        <td>{{$value_rot->nombre_asignatura}}</td>
                                        <td>{{$value_rot->nombre_hospital}}</td>
                                        <td>{{$value_rot->fecha_inicio}}</td>
                                        <td>{{$value_rot->fecha_termino}}</td>
                                        <td>{{$value_rot->fecha_inicio_encuesta}}</td>
                                        <td>{{$value_rot->nombre}}</td>
                                    </tr>
                                @endif
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
                    </div>
                    </div>
            </div>
        </div>
    @endforeach

    </div>
</div>

@endsection