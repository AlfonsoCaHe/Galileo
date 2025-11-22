@extends('layouts.default')

@section('title', 'Módulos del Proyecto: ' . $proyecto->proyecto)

@section('scripts')
    <script>
        $(document).ready(function() {
            // Inicialización de DataTables
            $('#modulos-datatable').DataTable({
                "language": {
                    // ... (Configuración de lenguaje DataTables, igual que en otras vistas) ...
                    "decimal": ",",
                    "emptyTable": "No hay módulos definidos en este proyecto.",
                    "info": "Monstrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                    "infoEmpty": "",
                    "infoFiltered": "",
                    "infoPostFix": "",
                    "thousands": ".",
                    "lengthMenu": "Mostrar _MENU_ registros",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Buscar:",
                    "zeroRecords": "No han encontrado registros",
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
                // Deshabilita la ordenación en la columna de Acciones (índice 3)
                "columnDefs": [
                    { "orderable": false, "targets": 3 } 
                ]
            });

            // Script para la confirmación de eliminación
            $('#modulos-datatable').on('click', '.delete-form button', function(e) {
                e.preventDefault();
                if (confirm('¿Estás seguro de que quieres eliminar este módulo?')) {
                    $(this).closest('form').submit();
                }
            });
        });
    </script>
@endsection

@section('content')
<div class="container my-5">
    <h1 class="mb-4 text-primary">Módulos del {{ $proyecto->proyecto }}</h1>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h4 class="alert-heading">¡Error en la Operación!</h4>
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
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif
    <div class="card shadow-lg p-4">
        {{-- Botón para Añadir Nuevo Módulo --}}
        <div class="d-flex justify-content-end mb-3">
            <a href="{{ route('gestion.modulos.create', ['proyecto_id' => $proyecto->id_base_de_datos]) }}"class="btn btn-success fw-bold">
                Nuevo Módulo
            </a>
        </div>

        <table id="modulos-datatable" class="table table-striped table-bordered" style="width:100%">
            <thead>
                <tr>
                    <th>Nombre del Módulo</th>
                    <th>Profesor Asignado</th>
                    <th>Alumnos Asignados</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($modulos as $modulo)
                    <tr>
                        <td>{{ $modulo->nombre }}</td>
                        <td>
                            @forelse ($modulo->profesores as $profesor)
                                <span class="badge bg-secondary me-1">{{ $profesor->nombre }}</span>
                            @empty
                                <span class="badge bg-danger">Sin Profesor(es) Asignado(s)</span>
                            @endforelse
                        </td> 
                        <td>
                            <span class="badge bg-info text-dark">{{ $modulo->alumnos->count() }} Alumnos</span>
                        </td>
                        <td>
                            {{-- Botón Editar --}}
                            <a href="{{ route('gestion.modulos.edit', ['proyecto_id' => $proyecto->id_base_de_datos, 'modulo_id' => $modulo->id_modulo]) }}" class="btn btn-sm btn-warning" title="Editar Módulo">
                                Editar
                            </a>
                            
                            {{-- Formulario para Eliminar --}}
                            <form action="{{ route('gestion.modulos.destroy', ['proyecto_id' => $proyecto->id_base_de_datos, 'modulo_id' => $modulo->id_modulo]) }}" method="POST" class="d-inline-block delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" title="Eliminar Módulo">
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
@endsection