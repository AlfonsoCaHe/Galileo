@extends('layouts.default')

@section('content')
<div class="container my-5">
    <h2>Crear Nueva Empresa</h2>

    <form action="{{ route('gestion.empresas.store') }}" method="POST">
        @csrf
        
        {{-- DATOS DE LA EMPRESA --}}
        <h3 class="mt-4">Datos de la Empresa</h3>
        <hr>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="cif_nif" class="form-label">CIF/NIF</label>
                <input type="text" class="form-control" id="cif_nif" name="cif_nif" value="{{ old('cif_nif') }}" required>
                @error('cif_nif') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="nombre" class="form-label">Nombre de la Empresa</label>
                <input type="text" class="form-control" id="nombre" name="nombre" value="{{ old('nombre') }}" required>
                @error('nombre') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="nombre_gerente" class="form-label">Nombre del Gerente</label>
                <input type="text" class="form-control" id="nombre_gerente" name="nombre_gerente" value="{{ old('nombre_gerente') }}" required>
                @error('nombre_gerente') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="nif_gerente" class="form-label">NIF del Gerente</label>
                <input type="text" class="form-control" id="nif_gerente" name="nif_gerente" value="{{ old('nif_gerente') }}" required>
                @error('nif_gerente') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>

        {{-- DATOS DEL PRIMER TUTOR --}}
        <h3 class="mt-5">Datos del Tutor Laboral</h3>
        <hr>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="tutor_nombre" class="form-label">Nombre</label>
                <input type="text" class="form-control" id="tutor_nombre" name="tutor_nombre" value="{{ old('tutor_nombre') }}" required>
                @error('tutor_nombre') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-6 mb-3">
                <label for="tutor_email" class="form-label">Email (Acceso Usuario)</label>
                <input type="email" class="form-control" id="tutor_email" name="tutor_email" value="{{ old('tutor_email') }}" required>
                @error('tutor_email') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" required>
                @error('password') <div class="text-danger">{{ $message }}</div> @enderror
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg mt-4">Crear Empresa</button>
        <a href="{{ route('gestion.empresas.index') }}" class="btn btn-secondary btn-lg mt-4">Cancelar</a>
    </form>
</div>
@endsection