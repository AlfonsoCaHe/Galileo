@extends('layouts.default')

@section('title', 'Detalle de ' . $alumno->nombre)

@section('content')
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-10 col-lg-8">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    
                    <!-- ENCABEZADO PRINCIPAL -->
                    <h1 class="card-title fw-bold text-primary mb-3 text-center">
                        Alumno: {{ $alumno->nombre }}
                    </h1>
                    <p class="text-muted border-bottom pb-3 mb-4">
                        <span class="badge bg-success me-2">
                            Proyecto: {{ $conexionEncontrada }}
                        </span>
                    </p>

                    <!-- SECCIÓN: TUTORÍA Y EMPRESA -->
                    <div class="mb-5 p-3 bg-light rounded-3 border">
                        <h4 class="fw-semibold text-secondary mb-3 mt-3">Tutoría Docente</h4>
                        @if ($alumno->tutorDocente)
                            <p class="mb-1">
                                <i class="bi bi-person-circle me-2"></i>
                                <strong>Tutor:</strong> {{ $alumno->tutorDocente->nombre }}
                            </p>
                        @else
                            <p class="text-warning mb-0">No tiene asignado un Tutor Docente.</p>
                        @endif
                        <h4 class="fw-semibold text-secondary mb-3 mt-3">Tutoría Laboral</h4>
                        @if ($alumno->tutorLaboral)
                            <p class="mb-1">
                                <i class="bi bi-person-circle me-2"></i>
                                <strong>Tutor:</strong> {{ $alumno->tutorLaboral->nombre }}
                            </p>
                            <p class="mb-0">
                                <i class="bi bi-building me-2"></i>
                                <strong>Empresa:</strong> {{ $alumno->tutorLaboral->empresa?->nombre ?? 'N/A' }}
                            </p>
                        @else
                            <p class="text-warning mb-0">No tiene asignado un Tutor Laboral.</p>
                        @endif
                    </div>

                    <!-- SECCIÓN: MÓDULOS -->
                    <h3 class="fw-bold text-dark mb-3">Módulos Matriculados</h3>
                    <ul class="list-group list-group-flush mb-5 border rounded-3">
                        @forelse ($alumno->modulos as $modulo)
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                {{ $modulo->nombre }}
                                <span class="badge bg-info text-dark rounded-pill">
                                    Módulo
                                </span>
                            </li>
                        @empty
                            <li class="list-group-item text-muted">No está matriculado en módulos.</li>
                        @endforelse
                    </ul>

                    <!-- SECCIÓN: TAREAS ASIGNADAS -->
                    <h3 class="fw-bold text-dark mb-4">Tareas Asignadas y Evaluación</h3>
                    
                    @forelse ($alumno->tareas as $tarea)
                        <div class="card mb-4 shadow-sm border-2 
                             {{ $tarea->apto ? 'border-success' : 'border-danger' }}">
                            <div class="card-header d-flex justify-content-between align-items-center 
                                        {{ $tarea->apto ? 'bg-success text-white' : 'bg-danger text-white' }} fw-bold">
                                <span>
                                    Tarea: {{ $tarea->actividad }}
                                </span>
                                <span class="badge bg-light text-dark">
                                    APTO: {{ $tarea->apto ? 'Sí' : 'No' }}
                                </span>
                            </div>
                            <div class="card-body p-3">
                                <h6 class="fw-semibold mb-2 text-primary">Criterios de Evaluación:</h6>
                                <ul class="list-group list-group-flush">
                                    @forelse ($tarea->criterios as $criterio)
                                        <li class="list-group-item py-1 px-0 border-0 text-dark">
                                            <i class="bi bi-check-circle-fill me-2 text-success"></i>
                                            {{ $criterio->nombre }}
                                        </li>
                                    @empty
                                        <li class="list-group-item py-1 px-0 border-0 text-muted fst-italic">No hay criterios asignados.</li>
                                    @endforelse
                                </ul>
                            </div>
                        </div>
                    @empty
                        <div class="alert alert-info text-center" role="alert">
                            No hay tareas asignadas.
                        </div>
                    @endforelse
                    
                </div>
            </div>
        </div>
    </div>
</div>
@endsection