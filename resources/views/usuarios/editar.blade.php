@extends('layouts.default')

@section('content')
<div class="container">
    <h2>Modificar Usuario: {{ $usuario->name }}</h2>

    <form method="POST" action="{{ route('usuarios.update', ['id' => $usuario->id]) }}">
        @csrf

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
        <a href="{{ route('usuarios.show') }}" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
@endsection