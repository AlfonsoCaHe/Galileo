@extends('layouts.default')

@extends('gestion.layouts.header')

@section('title', 'Gestión de Profesores')

@section('scripts')
    {{-- Scripts de DataTables --}}
    <script>
        $(document).ready(function() {
            // Inicialización de DataTables
            $('#profesores-datatable').DataTable({
                "language": {
                    "decimal": ",",
                    "emptyTable": "No hay profesores registrados",
                    "info": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
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
                // Configuración de columnas no ordenables
                "columnDefs": [
                    // Desactivamos ordenación en: Estado(1), Módulos(2) y Acciones(4)
                    { "orderable": false, "targets": [1, 2, 4] } 
                ]
            });
        });
    </script>
@endsection

@section('content')
<div class="container my-5">
    <h1 class="mb-4 texto">Gestión de Profesores</h1>

    {{-- Bloque de Errores --}}
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

    {{-- Bloque de Éxito --}}
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Botones de Acción --}}
    <div class="d-flex justify-content-end">
        <a href="{{ route('gestion.profesores.create') }}" class="btn btn-success fw-bold m-2 shadow-sm">
            <i class="bi bi-plus-circle-fill me-1"></i> Nuevo Profesor
        </a>
    </div>
    <div class="card shadow-lg p-4">
        {{-- CABECERA CON FILTRO --}}
        <div class="d-flex justify-content-end align-items-center mb-3">
            {{-- Filtro de Estado --}}
            <form action="{{ route('gestion.profesores.index') }}" method="GET" class="d-flex align-items-center">
                <label for="estado" class="form-label me-2 mb-0 fw-bold text-secondary">Estado:</label>
                <select name="estado" id="estado" class="form-select w-auto" onchange="this.form.submit()">
                    <option value="todos" {{ $filtro == 'todos' ? 'selected' : '' }}>Todos</option>
                    <option value="activos" {{ $filtro == 'activos' ? 'selected' : '' }}>Activos</option>
                    <option value="inactivos" {{ $filtro == 'inactivos' ? 'selected' : '' }}>Inactivos</option>
                </select>
            </form>
        </div>

        @if($profesores->isEmpty())
            <p class="alert alert-warning">No hay profesores registrados en la base de datos.</p>
        @else
            <table id="profesores-datatable" class="table table-striped table-hover w-100 align-middle">
                <thead>
                    <tr>
                        <th>Nombre del Profesor</th>
                        <th class="text-center">Estado</th>
                        <th>Módulos</th>
                        <th class="text-center">Alumnos</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($profesores as $profesor)
                    <tr>
                        {{-- 1. Nombre --}}
                        <td class="fw-bold text-primary">
                            {{ $profesor->nombre }}
                            @if(optional($profesor->user)->email)
                                <br>
                                <small class="text-muted fw-normal" style="font-size: 0.8rem;">
                                    {{ $profesor->user->email }}
                                </small>
                            @endif
                        </td>

                        {{-- 2. Estado (Toggle) --}}
                        <td class="text-center">
                            <form action="{{ route('gestion.profesores.toggle', $profesor->id_profesor) }}" 
                                  method="POST" 
                                  id="form-toggle-{{ $profesor->id_profesor }}">
                                @csrf
                                @method('PUT')
                                
                                <div class="form-check form-switch d-flex justify-content-center">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           role="switch"
                                           style="cursor: pointer; transform: scale(1.2);"
                                           onchange="this.form.submit()"
                                           {{ $profesor->activo ? 'checked' : '' }}>
                                </div>
                                <span class="badge {{ $profesor->activo ? 'bg-success' : 'bg-danger' }} mt-1">
                                    {{ $profesor->activo ? 'ACTIVO' : 'INACTIVO' }}
                                </span>
                            </form>
                        </td>

                        {{-- 3. Módulos --}}
                        <td>
                            @if(isset($profesor->modulos) && $profesor->modulos->isNotEmpty())
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($profesor->modulos as $modulo)
                                        <span class="badge bg-secondary text-white">
                                            {{ $modulo->nombre ?? 'Módulo' }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-muted small fst-italic">Sin módulos asignados</span>
                            @endif
                        </td>

                        {{-- 4. Alumnos --}}
                        <td class="text-center">
                            @php $total = $profesor->alumnos_count ?? 0; @endphp
                            <span class="badge rounded-pill {{ $total > 0 ? 'bg-info text-dark' : 'bg-light text-secondary border' }} p-2">
                                <i class="bi bi-people-fill me-1"></i> {{ $total }}
                            </span>
                        </td>

                        {{-- 5. Acciones --}}
                        <td class="text-center">
                            {{-- Ver --}}
                            <a href="{{ route('gestion.profesores.show', $profesor->id_profesor) }}" 
                               class="btn btn-sm btn-info text-white" 
                               title="Ver Detalles">
                                Ver
                            </a>

                            {{-- Editar --}}
                            <a href="{{ route('gestion.profesores.edit', $profesor->id_profesor) }}" 
                               class="btn btn-sm btn-warning" 
                               title="Editar">
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