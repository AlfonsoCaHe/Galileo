@extends('layouts.default')

@section('title', 'Editar Tutor Laboral: ' . $tutor->nombre)

@section('content')
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-7">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    <h2 class="card-title text-center mb-4 fw-bold text-primary">
                        Editar Tutor Laboral
                    </h2>
                    <h4 class="text-center mb-5 text-muted">
                        {{ $tutor->nombre }} (Empresa: {{ $tutor->empresa->nombre }})
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

                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            {{ session('success') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form action="{{ route('gestion.tutores.update', ['tutor_id' => $tutor->id_tutor_laboral]) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label for="nombre" class="form-label fw-semibold">Nombre Completo</label>
                            <input type="text" 
                                   name="nombre" 
                                   id="nombre" 
                                   class="form-control" 
                                   value="{{ old('nombre', $tutor->nombre) }}" 
                                   required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email de Acceso</label>
                            <input type="email" 
                                   name="email" 
                                   id="email" 
                                   class="form-control" 
                                   value="{{ old('email', $tutor->email) }}" 
                                   required>
                        </div>

                        <hr class="my-4">
                        
                        <p class="text-muted">Rellena los campos de contraseña solo si deseas cambiarla.</p>
                        
                        <div class="mb-3">
                            <label for="password" class="form-label fw-semibold">Nueva Contraseña</label>
                            <input type="password" 
                                   name="password" 
                                   id="password" 
                                   class="form-control">
                        </div>
                        
                        <div class="mb-3">
                            <label for="password_confirmation" class="form-label fw-semibold">Confirmar Nueva Contraseña</label>
                            <input type="password" 
                                   name="password_confirmation" 
                                   id="password_confirmation" 
                                   class="form-control">
                        </div>

                        <div class="d-flex justify-content-between mt-5">
                            <button type="submit" class="btn btn-primary btn-lg rounded-3 fw-bold shadow-sm">
                                Guardar Cambios
                            </button>
                            <a href="{{ route('gestion.empresas.edit', ['empresa_id' => $tutor->empresa_id]) }}" 
                               class="btn btn-secondary btn-lg rounded-3 fw-bold shadow-sm">
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