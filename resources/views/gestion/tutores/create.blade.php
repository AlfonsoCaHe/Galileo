@extends('layouts.default')

@extends('gestion.layouts.header')

@section('content')
<div class="container">
    <h2 class="texto">Crear Tutor Laboral para {{ $empresa->nombre }}</h2>
    <p class="texto">CIF/NIF: {{ $empresa->cif_nif }}</p>
    <form action="{{ route('gestion.tutores.store', ['empresa_id' => $empresa->id_empresa]) }}" method="POST">
        @csrf
        <div class="card shadow-sm p-4 border-0 mt-4 mb-4">
            {{-- Campo Nombre del Tutor --}}
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre del Tutor</label>
                <input type="text" class="form-control" id="nombre" name="nombre" value="{{ old('nombre') }}" required>
                @error('nombre') <div class="text-danger">{{ $message }}</div> @enderror
            </div>

            <div class="mb-3">
                <label for="dni" class="form-label">DNI: </label>
                <input type="text" class="form-control" id="dni" name="dni" value="{{ old('dni') }}">
                @error('dni') <div class="text-danger">{{ $message }}</div> @enderror
            </div>

            {{-- Campo Email (Username) --}}
            <div class="mb-3">
                <label for="email" class="form-label">Email (Usuario)</label>
                <input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required>
                @error('email') <div class="text-danger">{{ $message }}</div> @enderror
            </div>

            {{-- Campo Contraseña --}}
            <div class="mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required>
                @error('password') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>

        <button type="submit" class="btn btn-primary">Añadir</button>
        <a href="{{ route('gestion.empresas.index') }}" class="btn btn-danger ms-2">Cancelar</a>
    </form>
</div>
@endsection