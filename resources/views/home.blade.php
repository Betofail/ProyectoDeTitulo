@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col">
            <div class="card">
                <div class="card-header">Asignaturas Inscritas</div>

                <div class="card-body">
                    @if(is_null($asignatura))
                        <h1>No Tiene Asignaturas Inscritas</h1>
                    @else
                    <table class="table table-responsive" width="100%" cellspacing="0">
                        <thead>
                            <th>Link Encuesta</th>
                            <th>Nombre Asignatura</th>
                            <th>Docente A Cargo</th>
                            <th>Semestre</th>
                        </thead>
                        <tbody>
                        @foreach($asignatura as $key => $value)
                        <tr>
                            <td><a href="{{$value->link_encuesta}}">{{$value->link_encuesta}}</a></td>
                            <td>{{$value->Nombre}}</td>
                            <td>{{$value->nombre}}</td>
                            <td>{{$value->Semestre}}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card">
                <div class="card-header">Campus Clinico Vigentes</div>

                <div class="card-body">
                    @if(is_null($campus_clinico))
                        <h1>No tiene Campus Clinicos Inscritos</h1>
                    @else
                    <table class="table table-responsive">
                        <thead>
                            <th>Link Encuesta</th>
                            <th>Hospital</th>
                            <th>Fecha inicio</th>
                            <th>Fecha Termino</th>
                            <th>Nombre Asignatura</th>                            
                        </thead>
                        <tbody>
                            @foreach($campus_clinico as $key => $value)
                            <tr>
                                <td><a href="{{$value->link_encuesta}}">{{$value->link_encuesta}}</a></td>
                                <td>{{$value->nombre_hospital}}</td>
                                <td>{{$value->fecha_inicio}}</td>
                                <td>{{$value->fecha_termino}}</td>
                                <td>{{$value->nombre}}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection