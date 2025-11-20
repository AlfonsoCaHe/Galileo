@extends('layouts.default')

@section('content')
<div class="container">
    <div class="p-3">
        <div class="border p-3 m-2">
            <h1 class="text-center">Listado de Bases de Datos de Proyectos</h1>

            @if ($proyectos->isEmpty())
                <p>No hay bases de datos de proyectos almacenadas.</p>
            @else
                <ul class="list-group">
                    @foreach ($proyectos as $proyecto)
                        <li class="list-group-item">
                            <a href="{{ route('admin.alumnosProyecto', $proyecto->id_base_de_datos)}}" class="list-group-item list-group-item-action d-flex justify-content-center align-items-center">>
                            <strong>Proyecto:</strong> {{ $proyecto->proyecto }} 
                            | <strong>Conexión:</strong> {{ $proyecto->conexion }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
            <div class="border p-3 m-2">
                <x-database-desplegable/>

                {{-- BLOQUE PARA MOSTRAR MENSAJES DE ÉXITO --}}
                @if(session('success'))
                    <div class="alert alert-success mt-3" role="alert">
                        {{ session('success') }}
                    </div>
                @endif

                {{-- BLOQUE PARA MOSTRAR MENSAJES DE ERROR --}}
                @if(session('error'))
                    <div class="alert alert-danger mt-3" role="alert">
                        {{-- Aquí se mostrará el error capturado en el controlador --}}
                        <strong>¡Error!</strong> {{ session('error') }}
                    </div>
                @endif

                <h3>Crear un Nuevo Proyecto Bianual</h3>
                
                {{-- Llamada al componente crear proyecto --}}
                <x-admin.crear-proyecto-form :year-start="now()->year" /> 
                <a href="{{ route('admin.panel') }}">Ir al Panel de Administración</a>
            </div>
        </div>
    </div>
</div>
@endsection 