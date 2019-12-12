@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <div class="col">
        <div class="card text-center">
            <div class="card-header">
                <div class="dropdown">
                    <button class="btn btn-default dropdown-toggle" type="button" id="periodo_dropdown"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Periodo: {{$nombre_pe}}
                    </button>
                    <div class="dropdown-menu" aria-labelledby="periodo_dropdown">
                        @foreach ($periodos as $key => $item)
                        @if ($item->estado == 1 or $item->estado == 2)
                        <a class="dropdown-item"
                            href="{{route('cambio_periodo',['id' => $item->idPeriodo])}}">{{$item->descripcion}}</a>
                        @endif
                        @endforeach
                    </div>
                </div>
            </div>
            <form method="POST" action="{{route('asignar_asignatura')}}">
                @csrf

                <button class="btn btn-primary btn-block">Guardar Cambios</button>
                <div class="row">
                    <div class="col-sm-6">
                        <div class="card text-center">
                            <div class="card-header">
                                <h5>Asignaturas Que no Tendran Encuesta</h5>
                                <h5>asignaturas - codigo</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group" id="sin_encuestas">
                                    @foreach ($sin_encuesta as $key => $item)
                                    <input class="form-control" type="text" readonly="true"
                                        value="{{$item['nombre'].'-'.$item['codigo']}}">
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="card text-center">
                            <div class="card-header">
                                <h5>Asignaturas Que Tienen Encuesta</h5>
                                <h5>asignaturas - codigo</h5>
                            </div>
                            <div class="card-body">
                                <ul class="list-group" id="con_encuestas">
                                    @foreach ($con_encuesta as $key => $item)
                                    <input class="form-control" type="text" readonly="true"
                                        value="{{$item['nombre'].'-'.$item['codigo']}}">
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@section('script')
<script>
    $(document).ready(function(){
                $('#sin_encuestas').on('dblclick','input',function(){
                    var x = Math.floor((Math.random() * 10000000) + 1)
                    var node = $("<input>").val($(this).val()).attr({
                        type: 'text',
                        name: 'con_en/'+x,
                        readonly: 'true',
                        class: 'form-control'
                    });
                    $('#con_encuestas').append(node);
                    this.remove();
                });
                $('#con_encuestas').on('dblclick','input',function(){
                    var x = Math.floor((Math.random() * 10000000) + 1)
                    var node = $("<input>").val($(this).val()).attr({
                        type: 'text',
                        name: 'sin_en/'+x,
                        readonly: 'true',
                        class: 'form-control'
                    });
                    $('#sin_encuestas').append(node);
                    this.remove();
                })
            });
</script>
@endsection
@endsection
