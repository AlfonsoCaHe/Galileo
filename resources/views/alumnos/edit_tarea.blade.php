@extends('layouts.default')
<div class="container py-5">
    @include('alumnos.layouts.header')

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

    <div class="row justify-content-center mt-4">
        <div class="col-md-8 col-lg-5">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    <h2 class="card-title text-center mb-3 fw-bold text-primary">Editar Tarea</h2>
                </div>
                <div class="card-body">
                    <form action="{{ route('alumnado.updateTarea', ['proyecto_id' => $proyecto->id_base_de_datos, 'tarea_id' => $tareaPrincipal->id_tarea]) }}" method="POST" novalidate>
                        @csrf
                        @method('PUT')
                        {{-- 1. Tarea (No modificable por el alumno) --}}
                        <div class="mb-3">
                            <span class="fw-bold text-primary">{{ $tareaPrincipal->tarea }}</span>
                        </div>

                        {{-- 2. Fecha --}}
                        <div class="mb-3">
                            <label for="fecha" class="form-label font-weight-bold">Fecha</label>
                            <input 
                                type="date" 
                                class="form-control @error('fecha') is-invalid @enderror" 
                                id="fecha" 
                                name="fecha" 
                                value="{{ $tareaPrincipal->fecha ? \Carbon\Carbon::parse($tareaPrincipal->fecha)->format('Y-m-d') : '' }}"
                                required
                            >
                            @error('fecha')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- 3. Duración --}}
                        <div class="mb-4">
                            <label class="form-label font-weight-bold">Duración</label>
                            
                            <x-duration-select 
                                name="duracion" 
                                id="duracion"
                                class="form-control"
                                :selected="old('duracion', isset($tareaPrincipal) ? \Carbon\Carbon::parse($tareaPrincipal->duracion)->format('H:i') : '')"
                            />

                            @error('duracion')
                                <div class="invalid-feedback d-block">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- 4. Notas alumno --}}
                        <div class="mb-3">
                            <label for="notas_alumno" class="form-label font-weight-bold">Descripción de la tarea</label>
                            <input type="text" id="notas_alumno" name="notas_alumno" class="form-control @error('notas_alumno') is-invalid @enderror" value="{{old('notas_alumno') ?? $tareaPrincipal->notas_alumno}}" required>
                            @error('notas_alumno')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- Botones de Acción --}}
                        <div class="d-flex justify-content-between align-items-center">
                            <button type="submit" class="btn btn-success">
                                Guardar Tarea
                            </button>
                            <a href="{{ route('alumnado.tareas_pendientes', ['proyecto_id' => $proyecto->id_base_de_datos]) }}" class="btn btn-danger">
                                <i class="bi bi-arrow-left"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</div>