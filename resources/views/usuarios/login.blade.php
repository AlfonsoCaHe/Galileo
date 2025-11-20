@extends('layouts.default')

@section('title', 'Iniciar Sesión')

@section('content')
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-5">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    <h2 class="card-title text-center mb-3 fw-bold text-primary">
                        Iniciar Sesión
                    </h2>
                    <p class="text-center text-muted mb-4">
                        Accede a tu cuenta para continuar.
                    </p>

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

                    <form action="{{ url('/login') }}" method="POST">
                        @csrf

                        <!-- Campo Email -->
                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">
                                Correo Electrónico
                            </label>
                            <input id="email" name="email" type="email" autocomplete="email" required
                                   value="{{ old('email') }}"
                                   class="form-control rounded-3 @error('email') is-invalid @enderror"
                                   placeholder="tu.correo@ejemplo.com">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Campo Contraseña -->
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">
                                Contraseña
                            </label>
                            <input id="password" name="password" type="password" autocomplete="current-password"
                                   class="form-control rounded-3 @error('password') is-invalid @enderror"
                                   placeholder="••••••••">
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Checkbox Recordar y Enlace Olvidé Contraseña -->
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="remember" id="remember">
                                <label class="form-check-label text-muted" for="remember">
                                    Recordarme
                                </label>
                            </div>
                            <small>
                                <a href="#" class="text-decoration-none text-info">
                                    ¿Olvidaste tu contraseña?
                                </a>
                            </small>
                        </div>

                        <!-- Botón de Enviar -->
                        <div class="d-grid gap-2">
                            <button type="submit"
                                    class="btn btn-primary btn-lg rounded-3 fw-bold shadow-sm">
                                Acceder
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection