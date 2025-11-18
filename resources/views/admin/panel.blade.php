@extends('layouts.default')

@section('content')
<div class="container mt-5">
    
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
    
    <h2>Herramientas de Administración de BD</h2>
    <hr>
    
    <h3>Crear un Nuevo Proyecto Bianual</h3>
    
    {{-- Llamada al componente crear proyecto --}}
    <x-admin.crear-proyecto-form :year-start="now()->year" /> 
    
    {{-- Podrías añadir otros formularios de gestión aquí, como db:setup-test o db:clean-test --}}
</div>
@endsection 