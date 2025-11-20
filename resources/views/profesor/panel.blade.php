@extends('layouts.default')

@section('content')
<div class="container">
    <h1 class="text-center">
        PANEL DE CONTROL DE PROFESOR
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
@endsection