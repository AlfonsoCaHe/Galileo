@extends('layouts.default')

@section('title', 'Módulos del Proyecto: ' . $proyecto->proyecto)

@section('scripts')
    <script>
        $(document).ready(function() {
            // Inicialización de DataTables (Patrón visual estricto)
            $('#modulos-datatable').DataTable({
                "language": {
                    "decimal": ",",
                    "emptyTable": "No hay módulos definidos en este proyecto.",
                    "info": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                    "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                    "infoFiltered": "(filtrado de _MAX_ registros totales)",
                    "lengthMenu": "Mostrar _MENU_ registros",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Buscar:",
                    "zeroRecords": "No se encontraron coincidencias",
                    "paginate": {
                        "first": "Primero",
                        "last": "Último",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    },
                    "aria": {
                        "sortAscending": ": Click/return para ordenar ascendentemente",
                        "sortDescending": ": Click/return para ordenar descendentemente"
                    }
                },
                "columnDefs": [
                    { "orderable": false, "targets": [3] } // Desactivar orden en Acciones
                ]
            });
        });
    </script>
@endsection

@section('content')

<div class="container-fluid">
    @if(auth()->user()->isAdmin())
        @include('gestion.layouts.header')
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <h2 class="mb-4 texto">Módulos: {{ $proyecto->proyecto }}</h2>
        
        {{-- Botones de Acción --}}
        <div class="d-flex gap-4">
            <a href="{{ route('gestion.modulos.create', $proyecto->id_base_de_datos) }}" class="btn btn-success shadow-sm">
                <i class="bi bi-plus-circle-fill"></i>Nuevo Módulo
            </a>
            @if(auth()->user()->isAdmin())
            <a href="{{ route('gestion.proyectos.index') }}" class="btn btn-danger shadow-sm">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
            @endif
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h5 class="alert-heading"><i class="bi bi-exclamation-triangle-fill me-2"></i>Error</h5>
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
            <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Listado de Módulos Formativos</h6>
        </div>
        
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="modulos-datatable" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre del Módulo</th>
                            <th>Profesor(es) Asignado(s)</th>
                            <th class="text-center" style="width: 120px;">Alumnos</th>
                            <th class="text-center" style="width: 300px;">Gestión</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($modulos as $modulo)
                            <tr>
                                {{-- 1. Nombre --}}
                                <td class="fw-bold text-primary">
                                    {{ $modulo->nombre }}
                                </td>

                                {{-- 2. Profesores --}}
                                <td>
                                    @forelse ($modulo->profesores as $profesor)
                                        <span class="badge bg-light text-dark border border-secondary mb-1">
                                            <i class="bi bi-person-badge me-1"></i> {{ $profesor->nombre }}
                                        </span>
                                        <br>
                                    @empty
                                        <span class="badge bg-danger">Sin Asignar</span>
                                    @endforelse
                                </td>

                                {{-- 3. Alumnos --}}
                                <td class="text-center">
                                    <span class="badge rounded-pill bg-info text-dark border border-info p-2 px-3">
                                        <i class="bi bi-people-fill me-1"></i> {{ $modulo->alumnos->count() }}
                                    </span>
                                </td>

                                {{-- 4. Acciones --}}
                                <td class="text-center">
                                    {{-- Botón RAs y Criterios --}}
                                    <a href="{{ route('gestion.ras.index', ['proyecto_id' => $proyecto->id_base_de_datos, 'modulo_id' => $modulo->id_modulo]) }}" 
                                       class="btn btn-sm btn-dark text-white shadow-sm me-1" 
                                       title="Gestionar RAs y Criterios">
                                        </i>RAs
                                    </a>

                                    {{-- Tareas --}}
                                    <a href="{{ route('gestion.actividades.index', ['proyecto_id' => $proyecto->id_base_de_datos, 'modulo_id' => $modulo->id_modulo]) }}" 
                                       class="btn btn-sm btn-info text-white shadow-sm me-1" 
                                       title="Gestionar Diario y Tareas">
                                        Actividades
                                    </a>

                                    {{-- Botón Editar --}}
                                    <a href="{{ route('gestion.modulos.edit', ['proyecto_id' => $proyecto->id_base_de_datos, 'modulo_id' => $modulo->id_modulo]) }}" 
                                       class="btn btn-sm btn-warning shadow-sm me-1" 
                                       title="Editar Módulo">
                                        Editar
                                    </a>

                                    {{-- Formulario Eliminar --}}
                                    <form action="{{ route('gestion.modulos.destroy', ['proyecto_id' => $proyecto->id_base_de_datos, 'modulo_id' => $modulo->id_modulo]) }}" 
                                          method="POST" 
                                          class="d-inline"
                                          onsubmit="return confirm('¿Estás seguro de que deseas eliminar el módulo {{ $modulo->nombre }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger shadow-sm" title="Eliminar Módulo">
                                            Eliminar
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection