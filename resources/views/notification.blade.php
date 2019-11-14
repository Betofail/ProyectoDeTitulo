@extends('layouts.app')

@section('content')

@if($tipo == 'alumno')
	<div class="container-fluid">
		<div class="row">
		<div class="col-lg-2"></div>
		<div class="col-lg-8">
			<div class="card text-center">
				@if(is_null($emisores))
				<h6 class="card-subtitle"> no tiene notificaciones</h6>
				@else
				@foreach($emisores as $value)
				 <ul class="list-group list-group-flush">
					<li class="list-group-item"><h5 class="card-title">Notificacion de {{$value['name']}}</h5>
					<h6 class="card-subtitle mb-2 text-muted">Fecha: {{$value['created_at']}}</h6>
					<p class="card-text">{{$value['body']}}</p></li>
				</ul>
				@endforeach
				@endif
			</div>
		</div>
		</div>
	</div>
@elseif($tipo == 'OFEM' or $tipo == 'PA' or $tipo == 'SA')
	<div class="container">
		<div class="col">
		<div class="card text-center">
			<div class="card-header">Enviar Notificacion</div>
				<form method="POST" action="{{ route('notification.enviar') }}">
					@csrf 
					<div class="card-body">
						<div class="form-group">
							<select name="receptor_id" class="form-control">
							<option value="">Selecciona el usuario</option>
							@foreach($users as $user)
								<option  value="{{ $user->id }}">{{ $user->name }}</option>
							@endforeach
							</select>
						</div>
						<div class="form-group"> 
							<textarea name="body" class="form-control" placeholder="Escribe tu notificacion"></textarea>
						</div>
						<div class="form-group">
							<button class="btn btn-primary btn-block">Enviar</button>
						</div>
					</div>

				</form>
		</div>
		<div class="card">
			@if(is_null($emisores))
				<h6 class="card-subtitle"> no tiene notificaciones</h6>
			@else
			@foreach($emisores as $value)
			 <ul class="list-group list-group-flush">
				<li class="list-group-item"><h5 class="card-title">Notificacion de {{$value['name']}}</h5>
				<h6 class="card-subtitle mb-2 text-muted">Fecha: {{$value['created_at']}}</h6>
				<p class="card-text">{{$value['body']}}</p></li>
			</ul>
			@endforeach
			@endif
		</div>
		</div>
	</div>

@else
	<div class="container-fluid">
		<div class="row">
		<div class="col-lg-2"></div>
		<div class="col-lg-8">
			<div class="card text-center">
				@if(is_null($emisores))
				<h6 class="card-subtitle"> no tiene notificaciones</h6>
				@else
				@foreach($emisores as $value)
				 <ul class="list-group list-group-flush">
					<li class="list-group-item"><h5 class="card-title">Notificacion de {{$value['name']}}</h5>
					<h6 class="card-subtitle mb-2 text-muted">Fecha: {{$value['created_at']}}</h6>
					<p class="card-text">{{$value['body']}}</p></li>
				</ul>
				@endforeach
				@endif
			</div>
		</div>
		</div>
	</div>
@endif
@endsection