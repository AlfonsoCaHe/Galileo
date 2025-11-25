@extends('layouts.default')

@section('content')
<div class="container">
    <h2>Alumnos Asignados a {{ $profesor->nombre }}</h2>
    
    <div class="mb-3">
        <x-filtro-alumnos :profesorId="$profesor->id_profesor" :currentFiltro="$filtro"/>
    </div>

    @if ($alumnos->isEmpty())
        <p class="alert alert-warning">No se encontraron alumnos con el filtro seleccionado en ningún proyecto.</p>
    @else
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Nombre del Alumno</th>
                    <th>Proyecto</th> {{-- Nuevo campo --}}
                    <th>Rol en la relación</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($alumnos as $alumno)
                    <tr>
                        <td>{{ $alumno->nombre }}</td>
                        <td><span class="badge bg-info text-dark">{{ $alumno->proyecto_nombre }}</span></td> {{-- Muestra el proyecto --}}
                        <td>
                            @if ($alumno->tutor_docente_id === $profesor->id_profesor)
                                <span class="badge bg-primary">Tutor Docente</span>
                            @else
                                <span class="badge bg-secondary">Imparte Clase</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection