@extends('layouts.default')

@section('content')
<div class="container my-5">
    @if(@auth()->user()->isAdmin())
        @include('gestion.layouts.header')
    @endif
    <div class="row justify-content-center">

        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <h5 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>Error</h5>
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

        <div class="col-md-8 col-lg-7">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    <h2 class="text-info text-center">Modificar Usuario: {{ $usuario->name }}</h2>

                    <form method="POST" action="{{ route('gestion.usuarios.update', ['id' => $usuario->id]) }}">
                        @csrf
                        @method('PUT')
                        {{-- Campo Nombre --}}
                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre</label>
                            <input type="text" class="form-control" id="name" name="name" value="{{ old('name', $usuario->name) }}" required>
                            @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        {{-- Campo Email --}}
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="{{ old('email', $usuario->email) }}" required>
                            @error('email') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        {{-- Campo Contraseña --}}
                        <div class="mb-3">
                            <label for="password" class="form-label">Contraseña (Dejar vacío para no cambiar)</label>
                            <input type="password" class="form-control" id="password" name="password">
                            @error('password') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        
                        {{-- Campo Confirmar Contraseña --}}
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label">Confirmar Contraseña</label>
                            <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                        </div>

                        {{-- Campo Rol (NO SE PUEDE MODIFICAR) --}}
                        <div class="mb-3">
                            <label for="rol" class="form-label">Rol Actual</label>
                            <p class="form-control-static"><strong>{{ ucfirst($usuario->rol) }}</strong></p>
                        </div>
                    

                        <button type="submit" class="btn btn-primary">Actualizar Usuario</button>
                        <a href="{{ route('gestion.usuarios.index') }}" class="btn btn-danger ms-2">Cancelar</a>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection