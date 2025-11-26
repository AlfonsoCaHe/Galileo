@extends('layouts.default')

@section('title', 'Diario de Tareas')

@section('scripts')
    <script>
        $(document).ready(function() {
            $('#tareas-datatable').DataTable({
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
                "order": [[ 0, "desc" ]],
                "columnDefs": [{ "orderable": false, "targets": [5, 6] }] 
            });
        });
    </script>
@endsection

@section('content')
<div class="container-fluid">

    {{-- CABECERA --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">Tareas y Actividades</h1>
            <p class="mb-0 mt-1 text-gray-800 small">Módulo: <strong>{{ $modulo->nombre }}</strong></p>
        </div>
        <div class="d-flex">
            <a href="{{ route('gestion.modulos.index', $proyecto_id) }}" class="btn btn-secondary shadow-sm me-2">
                <i class="bi bi-arrow-left me-1"></i>Volver
            </a>
            @if(auth()->user()->isProfesor() || auth()->user()->isAdmin())
                <a href="{{ route('gestion.tareas.create', ['proyecto_id' => $proyecto_id, 'modulo_id' => $modulo->id_modulo]) }}" class="btn btn-primary shadow-sm">
                    <i class="bi bi-plus-circle-fill me-1"></i>Asignar Tarea
                </a>
            @endif
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Diario de Tareas</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="tareas-datatable" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 100px;">Fecha</th>
                            <th>Actividad / Tarea</th>
                            <th>Descripción Alumno</th>
                            <th class="text-center">Duración</th>
                            
                            {{-- Solo Profesor ve Criterios y Bloqueo --}}
                            @if(auth()->user()->isProfesor() || auth()->user()->isAdmin())
                                <th>Criterios</th>
                                <th class="text-center" style="width: 50px;"><i class="bi bi-lock-fill"></i></th>
                            @endif

                            <th class="text-center" style="width: 100px;">Calif.</th>
                            <th class="text-center" style="width: 120px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($tareas as $tarea)
                            <tr class="{{ $tarea->bloqueado ? 'table-secondary' : '' }}">
                                {{-- 1. Fecha --}}
                                <td class="text-center">
                                    {{ $tarea->fecha ? \Carbon\Carbon::parse($tarea->fecha)->format('d/m/Y') : '-' }}
                                </td>

                                {{-- 2. Nombre --}}
                                <td class="fw-bold text-primary">
                                    {{ $tarea->nombre }}
                                    @if(auth()->user()->isProfesor())
                                        <div class="small text-muted"><i class="bi bi-person me-1"></i>{{ $tarea->alumno->nombre ?? '?' }}</div>
                                    @endif
                                </td>

                                {{-- 3. Descripción --}}
                                <td><small>{{ Str::limit($tarea->notas_alumno, 60) ?? '-' }}</small></td>

                                {{-- 4. Duración --}}
                                <td class="text-center">{{ $tarea->duracion ? $tarea->duracion . ' h' : '-' }}</td>

                                {{-- 5 y 6. Criterios y Bloqueo (Solo Profesor) --}}
                                @if(auth()->user()->isProfesor() || auth()->user()->isAdmin())
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            @foreach($tarea->criterios as $criterio)
                                                <span class="badge bg-info text-dark" style="font-size: 0.7rem;">{{ $criterio->ce }}</span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        {{-- Botón para Bloquear/Desbloquear --}}
                                        <form action="{{ route('gestion.tareas.toggleBloqueo', ['proyecto_id' => $proyecto_id, 'tarea_id' => $tarea->id_tarea]) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <button type="submit" class="btn btn-sm {{ $tarea->bloqueado ? 'btn-danger' : 'btn-outline-secondary' }} border-0" title="{{ $tarea->bloqueado ? 'Desbloquear' : 'Bloquear edición' }}">
                                                <i class="bi {{ $tarea->bloqueado ? 'bi-lock-fill' : 'bi-unlock' }}"></i>
                                            </button>
                                        </form>
                                    </td>
                                @endif

                                {{-- 7. Calificación --}}
                                <td class="text-center">
                                    <span class="badge {{ $tarea->apto ? 'bg-success' : 'bg-danger' }}">
                                        {{ $tarea->apto ? 'APTO' : 'NO APTO' }}
                                    </span>
                                </td>

                                {{-- 8. Acciones --}}
                                <td class="text-center">
                                    @if(!$tarea->bloqueado || auth()->user()->isProfesor()) 
                                        {{-- Editar --}}
                                        <a href="#" class="btn btn-sm btn-warning shadow-sm me-1"><i class="bi bi-pencil-square"></i></a>
                                    @else
                                        <span class="text-muted small"><i class="bi bi-lock"></i></span>
                                    @endif
                                    
                                    @if(auth()->user()->isProfesor())
                                        {{-- Eliminar --}}
                                        <form action="{{ route('gestion.tareas.destroy', ['proyecto_id' => $proyecto_id, 'tarea_id' => $tarea->id_tarea]) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Borrar?');">
                                            @csrf @method('DELETE')
                                            <button class="btn btn-sm btn-danger shadow-sm"><i class="bi bi-trash-fill"></i></button>
                                        </form>
                                    @endif
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