@extends('layouts.default')

@section('title', 'Editar Módulo: ' . $modulo->nombre)

@section('content')
@include('gestion.layouts.header')
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    <h2 class="card-title text-center mb-4 fw-bold">
                        Editando {{ $modulo->nombre }} {{ $proyecto->proyecto }}
                    </h2>

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

                    <form action="{{ route('gestion.modulos.update', ['proyecto_id' => $proyecto->id_base_de_datos, 'modulo_id' => $modulo->id_modulo]) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-4">
                            <label for="nombre" class="form-label fw-semibold">Nombre del Módulo</label>
                            <input type="text" 
                                   name="nombre" 
                                   id="nombre" 
                                   class="form-control" 
                                   value="{{ old('nombre', $modulo->nombre) }}" 
                                   required>
                        </div>

                        <div class="mb-4">
                            <label for="profesores" class="form-label fw-semibold">
                                Profesor(es) Asignado(s)
                                <small class="text-muted">(Mantener Ctrl/Cmd para seleccionar varios)</small>
                            </label>
                            <select name="profesores[]" id="profesores" class="form-select" multiple size="5" required>
                                <option value="" disabled>Selecciona uno o más Profesores</option>
                                @foreach($profesores as $profesor)
                                    @php
                                        // Comprobamos si el ID del profesor está en el array de OLD o en el array asignado
                                        $is_selected = in_array($profesor->id_profesor, old('profesores', $profesores_asignados));
                                    @endphp
                                    <option value="{{ $profesor->id_profesor }}" 
                                            {{ $is_selected ? 'selected' : '' }}>
                                        {{ $profesor->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="alumnos" class="form-label fw-semibold">
                                Alumnos a Asignar
                                <small class="text-muted">(Mantener Ctrl/Cmd para seleccionar varios)</small>
                            </label>
                            <select name="alumnos[]" id="alumnos" class="form-select" multiple size="8">
                                @if($alumnos->isEmpty())
                                    <option value="" disabled>No hay alumnos en este proyecto.</option>
                                @endif
                                @foreach($alumnos as $alumno)
                                    <option value="{{ $alumno->id_alumno }}"
                                            {{ in_array($alumno->id_alumno, old('alumnos', $alumnos_asignados)) ? 'selected' : '' }}>
                                        {{ $alumno->nombre }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="d-flex justify-content-between mt-5">
                            <button type="submit" class="btn btn-success btn-lg rounded-3 fw-bold shadow-sm">
                                Guardar Cambios
                            </button>
                            <a href="javascript:history.back()" 
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