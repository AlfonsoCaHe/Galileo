@extends('layouts.default')

@section('content')
<div class="container mt-5">
    <h1 class="mb-4 text-primary">Herramientas de Administración de BD</h1>
    <hr>
    <a href="{{ route('gestion.proyectos.index') }}" class="btn btn-primary">Gestionar Proyectos</a>
    <a href="{{ route('usuarios.show') }}" class="btn btn-primary">Gestionar Usuarios</a>
    <a href="{{ route('gestion.empresas.index') }}" class="btn btn-primary">Gestionar Empresas</a>
    <a href="{{ route('gestion.alumnos.index') }}" class="btn btn-primary">Gestionar Alumnos</a>
    {{--<a href="{{ route('gestion.modulos.index') }}" class="btn btn-primary">Gestionar Modulos</a>--}}

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