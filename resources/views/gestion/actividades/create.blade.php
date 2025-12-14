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
        <div class="d-flex me-4">
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
    </div>

    @if ($errors->any())
        <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach</ul></div>
    @endif

    <form action="{{ route('gestion.actividades.store', ['proyecto_id' => $proyecto_id, 'modulo_id' => $modulo->id_modulo]) }}" method="POST">
        @csrf
        <div class="row">
            <div class="col-lg-12">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Detalles de la Actividad</h6>
                        <button type="submit" class="btn btn-success fw-bold shadow-sm">
                            <i class="bi bi-save me-1"></i> Crear Actividad
                        </button>
                    </div>
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
        </div>
    </form>
</div>

<script>
    function toggleAlumnos(check) {
        document.querySelectorAll('.chk-alumno').forEach(el => el.checked = check);
    }
</script>
@endsection