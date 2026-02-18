@extends('layouts.default')

@extends('gestion.layouts.header')

@section('content')
<div class="container my-5">
    <h2 class="texto">Crear Nueva Empresa</h2>

     @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h4 class="alert-heading">¡Error al eliminar el Proyecto!</h4>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <form action="{{ route('gestion.empresas.store') }}" method="POST">
        @csrf
        
        {{-- DATOS DE LA EMPRESA --}}
        <div class="card shadow-sm p-4 border-0">
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
        </div>

        <!-- <h2 class="texto">Disponibilidad de Plazas</h2>

        <div class="card shadow-sm p-4 mb-4 border-left-info">
            <div class="row g-3">
                {{-- 1º PERIODO --}}
                <div class="d-flex col-md-6">
                    <label class="form-label fw-bold text-secondary">
                        <span class="badge bg-primary me-1">1º</span> Periodo
                    </label>
                    <div class="input-group">
                        <input type="number" name="plazas[1]" class="form-control text-center fw-bold" min="0"placeholder="0" value="0">
                        <span class="input-group-text bg-light text-muted">alumnos</span>
                    </div>
                    {{-- Mensaje AJAX --}}
                    <div id="status-msg-1" class="small mt-1" style="height: 20px;"></div> 
                </div>

                {{-- 2º PERIODO --}}
                <div class="d-flex col-md-6">
                    <label class="form-label fw-bold text-secondary">
                        <span class="badge bg-secondary me-1">2º</span> Periodo
                    </label>
                    <div class="input-group">
                        <input type="number" name="plazas[2]" class="form-control text-center fw-bold" min="0" placeholder="0"  value="0">
                        <span class="input-group-text bg-light text-muted">alumnos</span>
                    </div>
                    {{-- Mensaje AJAX --}}
                    <div id="status-msg-2" class="small mt-2" style="height: 20px;"></div> 
                </div>
            </div>
        </div>
        <hr class="my-5"> -->

        {{-- DATOS DEL PRIMER TUTOR --}}
        <div class="card shadow-sm p-4 border-0 mt-4">
            <h3 class="mt-5">Datos del Tutor Laboral</h3>
            <hr>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="tutor_nombre" class="form-label">Nombre</label>
                    <input type="text" class="form-control" id="tutor_nombre" name="tutor_nombre" value="{{ old('tutor_nombre') }}" required>
                    @error('tutor_nombre') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label for="tutor_dni" class="form-label">DNI</label>
                    <input type="text" class="form-control" id="tutor_dni" name="tutor_dni" value="{{ old('tutor_dni') }}" required>
                    @error('tutor_dni') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label for="tutor_email" class="form-label">Email (Acceso Usuario)</label>
                    <input type="email" class="form-control" id="tutor_email" name="tutor_email" value="{{ old('tutor_email') }}" required>
                    @error('tutor_email') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
                <div class="col-md-6 mb-3">
                    <label for="password" class="form-label">Contraseña</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    @error('password') <div class="text-danger">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-primary btn-lg mt-4">Crear Empresa</button>
        <a href="{{ route('gestion.empresas.index') }}" class="btn btn-danger btn-lg mt-4 ms-2">Cancelar</a>
    </form>
</div>
@endsection