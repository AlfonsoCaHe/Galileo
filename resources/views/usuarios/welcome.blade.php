@extends('layouts.default')

@section('title', 'Landing Page')

@section('content')
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-lg border-0 rounded-4">
                <div class="card-body p-4 p-md-5">
                    <h2 class="card-title text-center mb-4 fw-bold text-success">
                        ¡Has iniciado sesión con éxito!
                    </h2>
                    
                    @if(Auth::check())
                        <p class="text-center text-muted">
                            Bienvenido, **{{ Auth::user()->name }}** (Rol: {{ Auth::user()->rol }}).
                        </p>
                    @endif

                    <h1 class="text-center mt-4 text-primary">
                        GALILEO
                    </h1>

                    <!-- Formulario de Logout -->
                    <div class="d-grid gap-2 col-6 mx-auto mt-5">
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-danger btn-lg rounded-3 fw-bold w-100 shadow-sm">
                                Cerrar Sesión
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection