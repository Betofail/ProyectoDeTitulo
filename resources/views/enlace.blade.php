@extends('layouts.app')

@section('content')

<div class="container">
    <div class="col">
        <div class="card text-center">
            <div class="card-header">
                <div class="dropdown">
                    <button class="btn btn-default dropdown-toggle" type="button" id="encuestas_dropdown"
                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        Nombre Encuesta: {{$nombre_en}}
                    </button>
                    <div class="dropdown-menu" aria-labelledby="encuestas_dropdown">
                        @foreach ($encuestas as $item)
                        @if ($item['active'] == "Y")
                    <a class="dropdown-item" href="{{route('cambio_encuesta',['id' => $item['sid']])}}">{{$item['surveyls_title']}}</a>
                        @endif
                        @endforeach
                    </div>
                </div>
            </div>
        <form method="POST" action="{{route('asignar_encuestas')}}">
            @csrf
            <input type="hidden" name='encuesta' value="{{$sid_en}}">
            <button class="btn btn-primary btn-block">Guardar Cambios</button>
            <div class="row">
                <div class="col-sm-6">
                    <div class="card text-center">
                        <div class="card-header">
                            <h5>Asignaturas Sin Encuestas</h5>
                            <h5>asignaturas - nrc / docente</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group" id="sin_encuestas">
                                @foreach ($asignaturas as $item)
                                <input class="form-group" type="text" readonly="true" value="{{$item->asign.' - '.$item->nrc.' / '.$item->nombre}}">
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6">
                    <div class="card text-center">
                        <div class="card-header">
                            <h5>Asignaturas Con Encuesta Actual</h5>
                            <h5>asignaturas - nrc / docente</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group" id="con_encuestas">
                                @foreach ($asignaturas_con as $item)
                                <input class="form-group" type="text" readonly="true" value="{{$item->asign.' - '.$item->nrc.' / '.$item->nombre}}">
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
                class: 'form-group'
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
                class: 'form-group'
            });
            $('#sin_encuestas').append(node);
            this.remove();
        })
    });


</script>
@endsection
@endsection
