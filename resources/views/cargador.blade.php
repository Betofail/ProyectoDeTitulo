@extends('layouts.app')
@section('content')

<div class="container">
    <div class="card bg-light mt-3">
        <div class="card-header">
            Archivos para cargar
        </div>
        <div class="card-body form-group">
            <form action="{{ route('import_alu') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <h5 class="text-center">Importar Alumnos</h5>
                <input type="file" name="file" class="form-control">
                <button class="btn btn-success">Alumnos</button>
            </form>
        </div>
        <div class="card-body form-group">
            <form action="{{route('import_docente')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <h5 class="text-center">Importar Docentes</h5>
                <input type="file" name="file_docente" class="form-control">
                <button class="btn btn-success">Docentes</button>
            </form>
        </div>
        <div class="card-body form-group">
            <form action="{{route('import_asing')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <h5 class="text-center">Importar Asignaturas</h5>
                <input type="file" name="file_asignatura" class="form-control">
                <button class="btn btn-success">Asignaturas</button>
            </form>
        </div>
        <div class="card-body form-group">
            <form action="{{route('import_sec')}}" method="POST" enctype="multipart/form-data">
                @csrf
                <h5 class="text-center">Importar Secciones</h5>
                <input type="file" name="file_section" class="form-control">
                <button class="btn btn-success">Cursos Alumnos</button>
            </form>
        </div>
    </div>
</div>
@endsection
