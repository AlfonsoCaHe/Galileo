@extends('layouts.default')

@section('content')
<div class="container">
    <h2>Seleccionar Alumno para ver su información</h2>

    <a href="{{route('home')}}" class="btn btn-primary">Volver</a>

    @if ($alumnos->isEmpty())
        <p class="alert alert-warning">No hay alumnos registrados en la base de datos.</p>
    @else
        <div class="list-group">
            @foreach ($alumnos as $alumno)
                <li>
                    <a href="{{ route('alumno.show', $alumno->id_alumno) }}" 
                    class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                        {{ $alumno->nombre }}
                    </a>
                </li>
            @endforeach
        </div>
    @endif
</div>
@endsection