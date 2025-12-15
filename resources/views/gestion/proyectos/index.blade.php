@extends('layouts.default')

@section('title', 'Listado de Proyectos')

@section('scripts')
    <script>
        $(document).ready(function() {
            // 1. Inicialización de DataTables
            const dataTable = $('#proyectos-datatable').DataTable({
                // Opciones de idioma (las mismas que usas)
                "language": {
                    "decimal": ",",
                    "emptyTable": "No hay datos en la tabla",
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
                // Deshabilitar ordenación y búsqueda en la columna de Acciones
                "columnDefs": [
                    { "orderable": false, "targets": [2, 3] } 
                ]
            });

            // Ocultamos las columnas 'id_base_de_datos' y 'finalizado' (usamos finalizado solo para el filtro)
            dataTable.column(1).visible(false);
            dataTable.column(2).visible(false); 

            // Estado inicial desde el controlador
            const estadoInicial = "{{ $estado_filtro }}";

            // 2. Función de Filtrado
            // DataTables requiere una función de filtro global para usar datos ocultos
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    const filtro = $('#filtro-estado').val();
                    const esFinalizado = data[2]; // Valor de la columna 'Finalizado' (0 o 1)

                    if (filtro === 'todos') { // Mostrar todos
                        return true;
                    }
                    if (filtro === 'activos' && esFinalizado === '0') { // Mostrar solo activos (finalizado = 0)
                        return true;
                    }
                    if (filtro === 'finalizados' && esFinalizado === '1') { // Mostrar solo finalizados (finalizado = 1)
                        return true; 
                    }
                    return false;
                }
            );

            // 3. Lógica del Desplegable (Detectar cambio)
            $('#filtro-estado').on('change', function() {
                dataTable.draw(); // Vuelve a dibujar la tabla aplicando el filtro
            });
            
            // 4. Aplicar el filtro inicial si no es 'todos'
            if (estadoInicial !== 'todos') {
                $('#filtro-estado').val(estadoInicial).trigger('change');
            }

            // 5. Petición AJAX para modificar el estado del proyecto
            $('#proyectos-datatable').on('change', '.finalizar-proyecto-checkbox', function() {
                const checkbox = $(this);
                const proyectoId = checkbox.data('proyecto-id');
                const nuevoEstado = checkbox.prop('checked') ? 1 : 0;
                
                // Obtener el token CSRF para la solicitud
                const csrfToken = $('meta[name="csrf-token"]').attr('content');

                $.ajax({
                    url: `/gestion/proyectos/${proyectoId}/estado`,
                    type: 'PUT', 
                    data: {
                        finalizado: nuevoEstado,
                        _token: csrfToken
                    },
                    success: function(response) {
                        // Mostrar notificación de éxito
                        alert(response.message); 
                        
                        // Actualizar la fila y el filtro en DataTables
                        // 1. Encontrar la fila que contiene el checkbox
                        const row = checkbox.closest('tr');
                        
                        // 2. Actualizamos el valor de la columna oculta (columna 2)
                        // Esto asegura que el filtro de DataTables refleje el nuevo estado
                        dataTable.cell(row, 2).data(nuevoEstado.toString()).draw();
                    },
                    error: function(xhr) {
                        // En caso de error, revierte el checkbox al estado anterior
                        checkbox.prop('checked', !checkbox.prop('checked'));
                        alert('Error al actualizar el estado del proyecto: ' + (xhr.responseJSON.message || 'Error de servidor'));
                    }
                });
            });
        });
    </script>
@endsection

@section('content')
<div class="container-fluid my-5">
    @if(auth()->user()->isAdmin())
        @include('gestion.layouts.header')
    @endif
    <h1 class="m-4 texto">Gestión de Proyectos</h1>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h4 class="alert-heading">¡Error al eliminar el Proyecto!</h4>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-end align-items-center">
        <div class="d-flex justify-content-between align-items-center p-3">
            {{-- Botón para crear el proyecto --}}
            <div class="me-4">
                <x-admin.crear-proyecto-form :year-start="now()->year" /> 
            </div>
        </div>
    </div>
    <div class="card shadow-lg p-4">
        {{-- Selector de proyectos (Activo/Inactivo) --}}
            <div class="d-flex align-items-center mb-2">
                <label for="filtro-estado" class="form-label me-2 mb-0 align-self-center fw-bold">Estado:</label>
                <select id="filtro-estado" class="form-select w-auto">
                    <option value="todos">Todos los Proyectos</option>
                    <option value="activos">Proyectos Activos</option>
                    <option value="finalizados">Proyectos Finalizados</option>
                </select>
            </div>
        @if($proyectos->isEmpty())
            <p class="alert alert-warning">No hay proyectos registrados.</p>
        @else
            <table id="proyectos-datatable" class="table table-striped table-hover w-100">
                <thead>
                    <tr>
                        <th class="text-start">Nombre del Proyecto</th>
                        <th class="d-none">ID de Conexión (UUID)</th>{{-- Oculto en la vista --}}
                        <th class="d-none">Finalizado (Filtro)</th>{{-- Oculto en la vista --}}
                        <th class="text-center">Finalizar</th>
                        <th class="text-center">Gestionar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($proyectos as $proyecto)
                    <tr>
                        {{-- 0. Nombre del proyecto--}}
                        <td class="text-start">{{ $proyecto->proyecto }}</td>

                        {{-- 1. Id del proyecto (Oculto en la vista) --}}
                        <td><span class="badge bg-secondary">{{ $proyecto->id_base_de_datos }}</span></td>
                        
                        {{-- 2. Valor para el Filtro de DataTables (Oculto en la vista) --}}
                        <td>{{ $proyecto->finalizado ? '1' : '0' }}</td> 
                        
                        {{-- 3. Checkbox para Finalizar --}}
                        <td class="text-center">
                            <div class="form-check form-switch d-inline-block">
                                <input class="form-check-input finalizar-proyecto-checkbox" 
                                    type="checkbox" 
                                    role="switch" 
                                    id="switch-{{ $proyecto->id_base_de_datos }}"
                                    data-proyecto-id="{{ $proyecto->id_base_de_datos }}"
                                    {{ $proyecto->finalizado ? 'checked' : '' }}>
                                <label class="form-check-label" for="switch-{{ $proyecto->id_base_de_datos }}"></label>
                            </div>
                        </td>

                        {{-- 4. Acciones --}}
                        <td class="text-center">
                            <a href="{{ route('gestion.modulos.index', ['proyecto_id' => $proyecto->id_base_de_datos]) }}"
                                class="btn btn-sm btn-info text-white ms-1" title="Gestionar Módulos">
                                Gestionar Módulos
                            </a>
                            <form action="{{ route('gestion.proyectos.destroy', ['proyecto_id' => $proyecto->id_base_de_datos]) }}" 
                                method="POST" 
                                class="d-inline"
                                onsubmit="return confirm('¿Estás seguro de que deseas eliminar el proyecto {{ $proyecto->proyecto }}? Esta acción es irreversible y solo se permitirá si la BD está vacía.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" title="Eliminar Proyecto">
                                    Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection