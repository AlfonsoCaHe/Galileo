@extends('layouts.default')

@section('content')
<div class="container">
    <h1 class="text-center">
        PANEL DE CONTROL DE TUTOR LABORAL
    </h1>
    <div>
        @auth
            <a href="{{ route('tutores.alumnos')}}" class="btn btn-primary m-3">
                Ver alumnos tutorizados
            </a>

            <p>Tu ID de Tutor es: {{ auth()->id() }}</p>
        @endauth
    </div>
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