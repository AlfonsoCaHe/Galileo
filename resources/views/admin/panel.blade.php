@extends('layouts.default')

@section('content')
<div class="container mt-5">
    <h2>Herramientas de Administración de BD</h2>
    <hr>
    <a href="{{ route('admin.proyectos') }}" class="btn btn-primary">Ir al Panel de Administración</a>
    <a href="{{ route('usuarios.show') }}" class="btn btn-primary">Ir al Listado de Usuarios</a>

    <!-- Formulario de Logout -->
    <div class="d-grid gap-2 col-6 mx-auto mt-5">
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-danger btn-lg rounded-3 fw-bold w-100 shadow-sm">
                Cerrar Sesión
            </button>
        </form>
    </div>
    {{-- Podrías añadir otros formularios de gestión aquí, como db:setup-test o db:clean-test --}}
</div>
@endsection 