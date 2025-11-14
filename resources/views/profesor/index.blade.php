@extends('layouts.default')

@section('content')
<div class="container">
    <h2>Seleccionar Profesor para Ver Alumnos</h2>
    <p>Selecciona un profesor para ver su lista consolidada de alumnos (Tutorados o por Módulo) en todos los proyectos.</p>

    @if ($profesores->isEmpty())
        <p class="alert alert-warning">No hay profesores registrados en la base de datos principal (Galileo).</p>
    @else
        <div class="list-group">
            @foreach ($profesores as $profesor)
                <a href="{{ route('profesor.alumnos', $profesor->id_profesor) }}" 
                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                    {{ $profesor->nombre }}
                    <span class="badge bg-primary rounded-pill">Ver Alumnos</span>
                </a>
            @endforeach
        </div>
    @endif
</div>
@endsection