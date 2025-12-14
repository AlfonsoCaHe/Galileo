@extends('layouts.default')
@section('content')
<div class="container-fluid">
    @if(auth()->user()->isAdmin())
        @include('gestion.layouts.header')
    @elseif(auth()->user()->isProfesor())
        @include('profesores.layouts.header')
    @endif

    {{-- CABECERA --}}
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="mb-0 texto">Actividad: <strong class="text-info">{{ $actividad->nombre }}</strong> <small class="text-info">({{ $modulo->nombre }})</small></h2>
        </div>
        <a href="javascript:history.back()" class="btn btn-danger shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    @if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="card-body">
        <form action="{{ route('gestion.actividades.update', ['proyecto_id' => $proyecto_id, 'modulo_id' => $modulo->id_modulo, 'actividad_id' => $actividad->id_actividad]) }}" method="POST">
            @csrf
            @method('PUT')
            
            <div class="row">
                <div class="col-lg-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Detalles de la Actividad</h6>
                            <button type="submit" class="btn btn-success fw-bold shadow-sm">
                                <i class="bi bi-save me-1"></i> Guardar Cambios
                            </button>
                        </div>
                        <div class="card-body">
                            
                            {{-- Nombre --}}
                            <div class="mb-3">
                                <label class="form-label fw-bold">Nombre de la Actividad</label>
                                {{-- CORRECCIÓN 2: old('nombre_input', $valor_defecto) --}}
                                <input type="text" name="nombre" class="form-control" 
                                       placeholder="Ej: Práctica 2. Montaje..." 
                                       value="{{ old('nombre', $actividad->nombre) }}" required>
                            </div>

                            {{-- Tarea (Nombre para el alumno) --}}
                            <div class="mb-3">
                                <label class="form-label fw-bold">Tarea</label>
                                <input type="text" name="tarea" class="form-control" 
                                       placeholder="Texto que aparecerá en el desplegable del alumno" 
                                       value="{{ old('tarea', $actividad->tarea) }}" required>
                            </div>

                            {{-- Descripción --}}
                            <div class="mb-3">
                                <label class="form-label fw-bold">Instrucciones / Descripción</label>
                                {{-- CORRECCIÓN 3: Textarea no tiene atributo value, el contenido va dentro --}}
                                <textarea name="descripcion" class="form-control" rows="4" 
                                          placeholder="Instrucciones para el alumno...">{{ old('descripcion', $actividad->descripcion) }}</textarea>
                            </div>

                            <hr>
                            
                            {{-- Criterios de Evaluación --}}
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
                                                {{-- CORRECCIÓN 4: Lógica para marcar el checkbox --}}
                                                {{-- Verificamos si el ID está en el array $criteriosIds que pasaste desde el controlador --}}
                                                <input class="form-check-input" 
                                                       type="checkbox" 
                                                       name="criterios[]" 
                                                       value="{{ $crit->id_criterio }}" 
                                                       id="cr-{{$crit->id_criterio}}"
                                                       {{ in_array($crit->id_criterio, old('criterios', $criteriosIds ?? [])) ? 'checked' : '' }}>
                                                
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
</div>
@endsection