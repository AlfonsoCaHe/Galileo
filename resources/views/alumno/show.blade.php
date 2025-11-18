@extends('layouts.default')

@section('content')
    <h2>Detalle del Alumno: {{ $alumno->nombre }}</h2>
    <p><strong>Proyecto/BD:</strong> {{ $conexionEncontrada }}</p>
    <hr>
    
    {{-- Módulos del Alumno --}}
    <h3>Módulos Matriculados</h3>
    @forelse ($alumno->modulos as $modulo)
        <li>{{ $modulo->nombre }}</li>
    @empty
        <p>No está matriculado en módulos.</p>
    @endforelse

    <hr>
    
    {{-- Tareas Asignadas --}}
    <h3>Tareas Asignadas</h3>
    @forelse ($alumno->tareas as $tarea)
        <h4>Tarea: {{ $tarea->actividad }}</h4>
        <p>Apto: <strong>{{ $tarea->apto ? 'Sí' : 'No' }}</strong></p>
        
        <h5>Criterios de Evaluación:</h5>
        <ul>
            @forelse ($tarea->criterios as $criterio)
                <li>{{ $criterio->nombre }}</li>
            @empty
                <li>No hay criterios asignados.</li>
            @endforelse
        </ul>
        <hr style="border-top: 1px dashed #ccc;">
    @empty
        <p>El alumno no tiene tareas asignadas.</p>
    @endforelse
    
    {{-- Datos del Tutor Laboral (Ya funcionando) --}}
    @if ($alumno->tutorLaboral)
        <p><strong>Tutor Laboral:</strong> {{ $alumno->tutorLaboral->nombre }}</p>
        <p><strong>Empresa:</strong> {{ $alumno->tutorLaboral->empresa?->nombre ?? 'N/A' }}</p>
    @endif
    
@endsection