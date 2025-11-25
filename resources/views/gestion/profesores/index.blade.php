@extends('layouts.default')

@section('title', 'Gestión de Profesores')

@section('scripts')
    <script>
        $(document).ready(function() {
            // 1. Inicialización de DataTables
            const dataTable = $('#profesores-datatable').DataTable({
                "language": {
                    "decimal": ",",
                    "emptyTable": "No hay profesores registrados.",
                    "info": "Monstrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                    "infoEmpty": "",
                    "infoFiltered": "",
                    "infoPostFix": "",
                    "thousands": ".",
                    "lengthMenu": "Mostrar _MENU_ registros",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Buscar:",
                    "zeroRecords": "No se han encontrado registros",
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
                "order": [[ 0, "asc" ]], // Ordenar por nombre de profesor
                "responsive": true,
                "columnDefs": [
                    { "orderable": false, "targets": [2, 3, 4] },//Deshabilitamos ordenación en Activo, Módulos, Alumnos y Acciones
                    { "visible": false, "targets": [0] }// Ocultamos la columna 0 (ID Profesor, mantenido para ordenación)
                ]
            });
            
            // 2. Manejo del Switch de Activo/Inactivo (AJAX o submit del form)
            $('.toggle-activo').on('change', function() {
                // Obtener el ID del profesor del atributo de datos
                const profesorId = $(this).data('profesor-id');
                // Obtener el formulario asociado y enviarlo
                $(`#toggle-form-${profesorId}`).submit();
            });

            // 3. Manejar el cambio del filtro de estado
            $('#estado_filtro').on('change', function() {
                const estado = $(this).val();
                // Redirigir a la misma ruta con el nuevo parámetro 'estado'
                window.location.href = "{{ route('gestion.profesores.index') }}?estado=" + estado;
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
        // Selecciona todos los inputs con la clase 'toggle-activo'
        const toggleSwitches = document.querySelectorAll('.toggle-activo');

        toggleSwitches.forEach(function(switchInput) {
            // Escucha el evento 'change' (cuando el usuario clica)
            switchInput.addEventListener('change', function() {
                
                // Encuentra el formulario más cercano al switch
                const form = this.closest('form');
                
                if (form) {
                    // Envía el formulario de forma programática
                    form.submit();
                }
                
                // Opcional: Deshabilitar el switch temporalmente para evitar doble clic
                this.disabled = true;
            });
        });
    });
    </script>
@endsection

@section('content')
<div class="container my-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fw-bold text-primary">Gestión de Profesores</h1>
    </div>

    {{-- Sección de alertas de la base de datos --}}
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

    {{-- Tabla de Profesores --}}
    <div class="card shadow-lg border-0 rounded-4 p-3">
        {{-- Menú de opciones --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 pb-3 border-bottom">
            
            {{-- Botones de Acción (Sección Izquierda) --}}
            <div class="d-flex flex-column flex-sm-row mb-3 mb-md-0">
                <a href="{{ route('gestion.profesores.create') }}" class="btn btn-success fw-bold me-md-3 mb-2 mb-sm-0 shadow-sm">
                    <i class="bi bi-person-plus-fill me-1"></i> Nuevo Profesor
                </a>
                <a href="{{ route('admin.panel') }}" class="btn btn-secondary fw-bold shadow-sm">
                    <i class="bi bi-arrow-left-circle-fill me-1"></i> Volver al Panel
                </a>
            </div>
            
            {{-- Filtro de Estado (Sección Derecha) --}}
            <div class="d-flex align-items-center">
                <label for="estado_filtro" class="form-label fw-semibold me-2 mb-0 text-nowrap">Filtrar por Estado:</label>
                <select id="estado_filtro" class="form-select w-auto" style="min-width: 150px;">
                    <option value="activos" {{ $estado_filtro == 'activos' ? 'selected' : '' }}>Profesores Activos</option>
                    <option value="inactivos" {{ $estado_filtro == 'inactivos' ? 'selected' : '' }}>Profesores Inactivos</option>
                    <option value="todos" {{ $estado_filtro == 'todos' ? 'selected' : '' }}>Todos</option>
                </select>
            </div>
        </div>
        @if($profesores->isEmpty())
            <p class="alert alert-warning">No hay profesores registrados en la base de datos.</p>
        @else
            <table id="profesores-datatable" class="table table-striped table-hover responsive" style="width:100%">
                <thead class="bg-primary text-white">
                    <tr>
                        <th scope="col" class="d-none">ID Profesor</th> <!-- Oculto para no mostrarlo -->
                        <th scope="col">Nombre Completo</th>
                        <th scope="col" class="text-center">Activo</th>
                        <th scope="col" class="text-center">Módulos Asignados</th>
                        <th scope="col" class="text-center">Alumnos Asignados</th>
                        <th scope="col" class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($profesores as $profesor)
                    <tr>
                        <td class="d-none">{{ $profesor->id_profesor }}</td>
                        <td>{{ $profesor->nombre }}</td>
                        
                        {{-- Columna Activo (Switch y Formulario) --}}
                        <td class="text-center">
                            {{-- Formulario para el toggle --}}
                            <form id="toggle-form-{{ $profesor->id_profesor }}" 
                                action="{{ route('gestion.profesores.toggle', $profesor->id_profesor) }}" method="POST" class="d-inline">
                                @csrf
                                @method('PUT')
                                <div class="form-check form-switch d-inline-block">
                                    <input class="form-check-input toggle-activo" 
                                        type="checkbox" 
                                        id="switch-{{ $profesor->id_profesor }}" 
                                        data-profesor-id="{{ $profesor->id_profesor }}"
                                        name="activo_toggle" 
                                        role="switch"
                                        {{ $profesor->activo ? 'checked' : '' }}>
                                    <label class="form-check-label visually-hidden" for="switch-{{ $profesor->id_profesor }}">Activo</label>
                                </div>
                            </form>
                        </td>

                        {{-- Columna Módulos Asignados --}}
                        @php
                            // Obtenemos las estadísticas ya calculadas en el Controller
                            $statsProfesor = $stats[$profesor->id_profesor] ?? ['modulos_total' => 0, 'alumnos_total' => 0, 'es_tutor_docente' => false];
                        @endphp
                        <td class="text-center">
                            <span class="badge {{ $statsProfesor['modulos_total'] > 0 ? 'bg-primary' : 'bg-secondary' }} p-2">
                                <i class="bi bi-book me-1"></i> {{ $statsProfesor['modulos_total'] }}
                            </span>
                            @if ($statsProfesor['es_tutor_docente'])
                                <span class="badge bg-info text-dark mt-1 d-block mx-auto" style="width: fit-content;">
                                    Tutor Docente
                                </span>
                            @endif
                        </td>

                        {{-- Columna Alumnos Asignados --}}
                        <td class="text-center">
                            <span class="badge {{ $statsProfesor['alumnos_total'] > 0 ? 'bg-success' : 'bg-secondary' }} p-2">
                                <i class="bi bi-people-fill me-1"></i> {{ $statsProfesor['alumnos_total'] }}
                            </span>
                        </td>
                        
                        {{-- Columna Acciones --}}
                        <td class="text-center">
                            {{-- Botón Ver --}}
                            <a href="{{ route('gestion.profesores.show', $profesor->id_profesor) }}" class="btn btn-sm btn-info text-white" title="Ver Detalles">
                                Ver
                            </a>
                            <a href="{{ route('gestion.profesores.edit', $profesor->id_profesor) }}" class="btn btn-sm btn-warning" title="Editar Profesor">
                                Editar
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection