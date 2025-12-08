@extends('layouts.default')

@section('content')
@include('gestion.layouts.header')
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-5">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    <h2 class="card-title text-center mb-3 fw-bold text-primary">
                        Modificar Datos Alumno: {{ $alumno->nombre }}
                    </h2>
                    <!-- Bloque de Errores de Validación de Laravel -->
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

                    <form action="{{ route('gestion.alumnos.update', ['proyecto_id' => $proyecto, 'alumno_id' => $alumno->id_alumno]) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Campo Nombre -->
                        <div class="mb-3">
                            <label for="nombre" class="form-label fw-semibold">
                                Nombre
                            </label>
                            <input id="nombre" name="nombre" required
                                   value="{{ $alumno->nombre }}"
                                   class="form-control rounded-3 @error('nombre') is-invalid @enderror"
                                   placeholder="Nombre">
                            @error('nombre')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Campo Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">
                                Correo Electrónico
                            </label>
                            <input id="email" name="email" type="email" autocomplete="email" required
                                   value="{{ $alumno->email }}"
                                   class="form-control rounded-3 @error('email') is-invalid @enderror"
                                   placeholder="tu.correo@ejemplo.com">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Campo Contraseña -->
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">
                                Contraseña (Dejar vacío para no cambiar)
                            </label>
                            <input id="password" name="password" type="password" autocomplete="new-password"
                                class="form-control rounded-3 @error('password') is-invalid @enderror"
                                placeholder="••••••••">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <!-- Campo confirmar Contraseña --> 
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirmar Contraseña</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                        </div>

                        <!-- Botón de Enviar -->
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg rounded-3 fw-bold shadow-sm">
                                Actualizar
                            </button>
                        </div>
                        <!-- Botón de Cancelar -->
                        <div class="d-grid gap-2 mt-3">
                            <a href="javascript:history.back()" class="btn btn-lg btn-danger shadow-sm">
                                <i class="bi bi-arrow-left me-1"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>