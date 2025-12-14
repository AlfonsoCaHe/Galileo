@extends('layouts.default')

@section('content')
@include('alumnos.layouts.header')
<div class="container mx-auto p-4">
    
    {{-- Cabecera con info del Proyecto --}}
    <div>
        <h2 class="fw-bold texto">Panel del Alumno</h2>
        <p class="texto">Proyecto: <span class="font-semibold text-warning">{{ $proyecto->proyecto ?? 'Proyecto sin asignar' }}</span></p>
        <div>
            <p class="texto">Tutor Laboral: <span class="font-semibold text-warning">{{ $alumno->tutor_laboral ?? 'Sin asignar' }}</span></p>
        </div>
        <div>
            <p class="texto">Tutor Docente: <span class="font-semibold text-warning">{{ $alumno->tutor_docente ?? 'Sin asignar' }}</span></p>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <h2 class="font-bold text-lg mb-2">Listado de Módulos Matriculados</h2>
            <ul class="list-disc list-inside text-gray-600">
                @foreach($modulos as $modulo)
                    <li>{{ $modulo->nombre }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    
</div>
@endsection