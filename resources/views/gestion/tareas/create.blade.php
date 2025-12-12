@extends('layouts.default')

@section('title', 'Nueva Tarea')

@section('content')
@if(auth()->user()->isAdmin())
    @include('gestion.layouts.header')
@elseif(auth()->user()->isProfesor())
    @include('profesores.layouts.header')
@endif
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-4 mt-4 texto">Nueva Actividad / Tarea</h2>
        @if(auth()->user()->isAdmin())
        <a href="{{ route('gestion.tareas.index', ['proyecto_id' => $proyecto_id, 'modulo_id' => $modulo->id_modulo]) }}" class="btn btn-danger shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Cancelar
        </a>
        @elseif(auth()->user()->isProfesor())
        <a href="{{ route('profesores.modulos.alumnos', ['proyecto_id' => $proyecto_id, 'modulo_id' => $modulo->id_modulo]) }}" class="btn btn-danger shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Cancelar
        </a>
        @endif
    </div>

    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach</ul></div>
    @endif

    <form action="{{ route('gestion.tareas.store', ['proyecto_id' => $proyecto_id, 'modulo_id' => $modulo->id_modulo]) }}" method="POST">
        @csrf
        
        <div class="row">
            {{-- COLUMNA IZQUIERDA: Definición de la Tarea --}}
            @if(auth()->user()->isAdmin())
            <div class="col-lg-8">
            @else
            <div class="col-lg-12">
            @endif
                <div class="card shadow mb-4">
                    <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Detalles de la Actividad</h6></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Nombre de la Actividad</label>
                            <input type="text" name="nombre" class="form-control" placeholder="Ej: Práctica 2. Montaje..." required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tarea</label>
                            <input type="text" name="tarea" class="form-control" placeholder="Texto que aparecerá en el desplegable del alumno" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Instrucciones / Descripción</label>
                            <textarea name="descripcion" class="form-control" rows="4" placeholder="Instrucciones para el alumno..."></textarea>
                        </div>
                        
                        <hr>
                        <label class="form-label fw-bold text-primary">Criterios de Evaluación (Concreción)</label>
                        <div class="accordion" id="accCriterios">
                            @foreach($modulo->ras as $ra)
                                <div class="accordion-item">
                                    <h2 class="accordion-header" id="h-{{$ra->id_ras}}">
                                        <button class="accordion-button collapsed py-2 bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#c-{{$ra->id_ras}}">
                                            <strong>{{ $ra->codigo }}</strong>: {{ Str::limit($ra->descripcion, 60) }}
                                        </button>
                                    </h2>
                                    <div id="c-{{$ra->id_ras}}" class="accordion-collapse collapse" data-bs-parent="#accCriterios">
                                        <div class="accordion-body p-2">
                                            @foreach($ra->criterios as $crit)
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="criterios[]" value="{{ $crit->id_criterio }}" id="cr-{{$crit->id_criterio}}">
                                                    <label class="form-check-label small" for="cr-{{$crit->id_criterio}}">
                                                        <span class="badge bg-secondary me-1">{{ $crit->ce }}</span> {{ $crit->descripcion }}
                                                    </label>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            @if(auth()->user()->isAdmin())
            {{-- COLUMNA DERECHA: Selección de Alumnos --}}
            <div class="col-lg-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">Asignar a Alumnos</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="toggleAlumnos(true)">Todos</button>
                    </div>
                    <div class="card-body" style="max-height: 500px; overflow-y: auto;">
                        @forelse($modulo->alumnos as $alumno)
                            <div class="form-check mb-2">
                                <input class="form-check-input chk-alumno" type="checkbox" name="alumnos[]" value="{{ $alumno->id_alumno }}" id="al-{{$alumno->id_alumno}}" checked>
                                <label class="form-check-label" for="al-{{$alumno->id_alumno}}">
                                    <i class="bi bi-person me-1 text-gray-400"></i>{{ $alumno->nombre }}
                                </label>
                            </div>
                        @empty
                            <p class="text-muted text-center small">No hay alumnos matriculados en este módulo.</p>
                        @endforelse
                    </div>
                    <div class="card-footer bg-white border-top-0">
                        <button type="submit" class="btn btn-success w-100 fw-bold shadow-sm">
                            <i class="bi bi-save me-1"></i> Crear y Asignar Tareas
                        </button>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </form>
</div>

<script>
    function toggleAlumnos(check) {
        document.querySelectorAll('.chk-alumno').forEach(el => el.checked = check);
    }
</script>
@endsection