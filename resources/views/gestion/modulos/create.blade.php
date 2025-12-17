@extends('layouts.default')

@section('title', 'Crear Módulo en Proyecto: ' . $proyecto->proyecto)

@section('content')
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    <h2 class="card-title text-center mb-4 fw-bold text-primary">
                        Crear Nuevo Módulo
                    </h2>
                    <h4 class="text-center mb-5 text-muted">
                        Proyecto: **{{ $proyecto->proyecto }}**
                    </h4>

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

                    <form action="{{ route('gestion.modulos.store', ['proyecto_id' => $proyecto->id_base_de_datos]) }}" method="POST">
                        @csrf
                        
                        <div class="mb-4">
                            <label for="nombre" class="form-label fw-semibold">Nombre del Módulo</label>
                            <input type="text" 
                                   name="nombre" 
                                   id="nombre" 
                                   class="form-control" 
                                   value="{{ old('nombre') }}" 
                                   required>
                        </div>

                        <div class="mb-4">
                            <label for="unidad" class="form-label fw-semibold">Unidad</label>
                            <input type="text" 
                                   name="unidad" 
                                   id="unidad" 
                                   class="form-control" 
                                   value="{{ old('unidad') }}" 
                                   required>
                        </div>

                        <div class="mb-4">
                            <label for="profesores" class="form-label fw-semibold">
                                Profesor(es) Asignado(s)
                                <small class="text-muted">(Mantener Ctrl/Cmd para seleccionar varios)</small>
                            </label>
                            {{-- ¡IMPORTANTE! Cambiar name="profesor_id" a name="profesores[]" y añadir 'multiple' --}}
                            <select name="profesores[]" id="profesores" class="form-select" multiple size="5" required>
                                <option value="" disabled>Selecciona uno o más Profesores</option>
                                @foreach($profesores as $profesor)
                                    <option value="{{ $profesor->id_profesor }}" 
                                            {{ in_array($profesor->id_profesor, old('profesores', [])) ? 'selected' : '' }}>
                                        {{ $profesor->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="alumnos" class="form-label fw-semibold">
                                Alumnos a Asignar (Opcional)
                                <small class="text-muted">(Mantener Ctrl/Cmd para seleccionar varios)</small>
                            </label>
                            <select name="alumnos[]" id="alumnos" class="form-select" multiple size="8">
                                @if($alumnos->isEmpty())
                                    <option value="" disabled>No hay alumnos en este proyecto.</option>
                                @endif
                                @foreach($alumnos as $alumno)
                                    <option value="{{ $alumno->id_alumno }}"
                                            {{ in_array($alumno->id_alumno, old('alumnos', [])) ? 'selected' : '' }}>
                                        {{ $alumno->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="d-flex justify-content-between mt-5">
                            <button type="submit" class="btn btn-success btn-lg rounded-3 fw-bold shadow-sm">
                                Crear Módulo
                            </button>
                            <a href="{{ route('gestion.modulos.index', ['proyecto_id' => $proyecto->id_base_de_datos]) }}" 
                               class="btn btn-danger btn-lg rounded-3 fw-bold shadow-sm">
                                Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection