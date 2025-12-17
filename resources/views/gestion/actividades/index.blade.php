@extends('layouts.default')

@section('title', 'Listado de Tareas')

@section('scripts')
    <script>
        $(document).ready(function() {
            // 1. Inicializar DataTables
            var table = $('#tareas-datatable').DataTable({
                "language": {
                    "decimal": ",",
                    "emptyTable": "No hay tareas registradas.",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_",
                    "infoEmpty": "",
                    "infoFiltered": "(filtrado)",
                    "lengthMenu": "Mostrar _MENU_",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Buscar:",
                    "zeroRecords": "No hay coincidencias",
                    "paginate": { "first": "First", "last": "Last", "next": "Next", "previous": "Prev" }
                },
                "columnDefs": [
                    { "orderable": false, "targets": [2, 3, 4] } // Desactivar orden en Criterios, Alumnos y Acciones
                ],
                // IMPORTANTE: Cada vez que DataTables redibuje la tabla (paginación, filtro),
                // reiniciamos los Popovers. Si no, dejan de funcionar en la página 2.
                "drawCallback": function(settings) {
                    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'))
                    var popoverList = popoverTriggerList.map(function (popoverTriggerEl) {
                        return new bootstrap.Popover(popoverTriggerEl, { html: true }) // html: true permite listas
                    })
                }
            });
        });
    </script>
@endsection

@section('content')
<div class="container-fluid">
    @if(auth()->user()->isAdmin())
        @include('gestion.layouts.header')
    @else(auth()->user()->isProfesor())
        @include('profesores.layouts.header')
    @endif

    {{-- Cabecera Simple --}}
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <h2 class="mb-0 texto">Actividades: <strong class="text-info">{{ $modulo->nombre }}</strong></h2>
        <div class="d-flex gap-2">
            @if(auth()->user()->isProfesor() || auth()->user()->isAdmin())
                <a href="{{ route('gestion.actividades.create', ['proyecto_id' => $proyecto_id, 'modulo_id' => $modulo->id_modulo]) }}" class="btn btn-success shadow-sm">
                    <i class="bi bi-plus-circle-fill me-1"></i> Nueva Actividad
                </a>
            @endif
            @if(auth()->user()->isAdmin())
            <a href="{{ route('gestion.modulos.index', ['proyecto_id' => $proyecto_id]) }}" class="btn btn-danger shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            @endif
            @if(auth()->user()->isProfesor())
            <a href="{{ route('profesores.modulos') }}" class="btn btn-danger shadow-sm">
                <i class="bi bi-arrow-left me-1"></i> Volver
            </a>
            @endif
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="tareas-datatable" width="100%">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 25%;">Nombre Actividad</th>
                            <th style="width: 30%;">Tarea</th>
                            <th style="width: 20%;">Criterios (RAs)</th>
                            <th class="text-center" style="width: 10%;">Alumnos</th>
                            <th class="text-center" style="width: 15%;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($actividades as $actividad)
                            <tr>
                                {{-- 1. NOMBRE --}}
                                <td class="fw-bold text-primary">{{ $actividad->nombre }}</td>

                                {{-- 2. TAREA --}}
                                <td>
                                    <small class="text-muted">{{ Str::limit($actividad->tarea, 80) ?? 'Sin título' }}</small>
                                </td>

                                {{-- 3. CRITERIOS --}}
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @forelse($actividad->criterios as $criterio)
                                            <span class="badge bg-light text-dark border" title="{{ $criterio->descripcion }}">
                                                {{-- Usamos optional por seguridad --}}
                                                <strong>{{ optional($criterio->ras)->codigo }}</strong> {{ $criterio->ce }}
                                            </span>
                                        @empty
                                            <span class="text-muted small fst-italic">Sin criterios</span>
                                        @endforelse
                                    </div>
                                </td>

                                {{-- 4. ALUMNOS (POPOVER) --}}
                                <td class="text-center">
                                    @php
                                        $datos = $infoAlumnos[$actividad->nombre] ?? ['total' => 0, 'nombres' => ''];
                                    @endphp
                                    <button type="button" 
                                            class="btn btn-sm btn-outline-info rounded-pill fw-bold border-0"
                                            data-bs-toggle="popover" 
                                            data-bs-trigger="hover focus"
                                            title="Alumnos con la Actividad" 
                                            data-bs-content="{{ $datos['nombres'] }}">
                                        <i class="bi bi-people-fill me-1"></i> {{ $datos['total'] }}
                                    </button>
                                </td>

                                {{-- 5. ACCIONES --}}
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-2">
                                        @if(auth()->user()->isProfesor() || auth()->user()->isAdmin())

                                            {{-- EDITAR --}}
                                            <a href="{{ route('gestion.actividades.edit', ['proyecto_id' => $proyecto_id, 'modulo_id' => $modulo->id_modulo, 'actividad_id' => $actividad->id_actividad ]) }}" 
                                               class="btn btn-sm btn-warning shadow-sm">
                                                <i class="bi bi-pencil-square"></i>Editar
                                            </a>

                                            {{-- ELIMINAR --}}
                                            <form action="{{ route('gestion.actividades.destroy', ['proyecto_id' => $proyecto_id, 'modulo_id' => $modulo->id_modulo, 'actividad_id' => $actividad->id_actividad]) }}" 
                                                  method="POST" class="d-inline" onsubmit="return confirm('¿Borrar esta actividad?');">
                                                @csrf @method('DELETE')
                                                <button class="btn btn-sm btn-danger shadow-sm"><i class="bi bi-trash-fill"></i>Eliminar</button>
                                            </form>
                                        @endif
                                    </div>
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