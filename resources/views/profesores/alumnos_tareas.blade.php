@extends('layouts.default')

@push('scripts')
<script>
    $(document).ready(function() {
        $('#tablaTareas').DataTable({
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
            order: [[ 1, "desc" ]] // Ordenar por fecha descendente por defecto
        });
    });
</script>
@endpush

@section('content')
@if(auth()->user()->isProfesor())
    @include('profesores.layouts.header')
@endif
<div class="container-fluid py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold texto">Historial de Tareas</h2>
            <h5 class="texto">Alumno: <span class="text-warning">{{ $alumno->nombre }}</span></h5>
            <p class="small texto mb-0">Módulo: <span class="text-warning">{{ $modulo->nombre }}</span> | Proyecto: <span class="text-warning">{{ $proyecto->proyecto }}</span></p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="javascript:history.back()" class="btn btn-danger d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left-circle"></i> Volver
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold text-primary"><i class="bi bi-journal-text me-2"></i>Tareas Entregadas</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tablaTareas" class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Descripción / Título</th>
                            <th>Anotaciones del alumno</th>
                            <th class="text-center">Fecha Entrega</th>
                            <th>Duración</th>
                            <th class="text-center">Calificación</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tareas as $tarea)
                        <tr>
                            {{-- Nombre --}}
                            <td>
                                <span class="fw-bold text-primary d-block">{{ $tarea->nombre }}</span>
                            </td>
                            {{-- Descripción --}}
                            <td>
                                <span class="text-muted d-block">{{ $tarea->descripcion ?? 'Sin descripción' }}</span>
                            </td>

                            {{-- Notas del alumno --}}
                            <td>
                                <span class="text-muted d-block">{{ $tarea->notas_alumno ?? 'Sin descripción' }}</span>
                            </td>
                            
                            {{-- Fecha de creación --}}
                            <td class="text-center">
                                @if(isset($tarea->created_at))
                                    {{ \Carbon\Carbon::parse($tarea->created_at)->format('d/m/Y H:i') }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>

                            {{-- Duración --}}
                            <td class="text-center">
                                <x-duration-select 
                                    :selected="$tarea->duracion"
                                    :url="route('gestion.tareas.updateDuracion', ['proyecto_id' => $proyecto->id_base_de_datos, 'tarea_id' => $tarea->id_tarea])"
                                    :disabled="true"
                                />
                            </td>
                            
                            {{-- Calificación --}}
                            <td class="text-center position-relative">
                                <div class="form-check d-flex justify-content-center">
                                    <input class="form-check-input border-2 border-secondary" type="checkbox" style="cursor: pointer; transform: scale(1.2);" disabled
                                        {{-- Estado actual --}}
                                        {{ $tarea->apto ? 'checked' : '' }}>
                                </div>
                            </td>
                            
                            {{-- Acciones --}}
                            <td class="text-end">
                                <a href="{{ route('gestion.tareas.edit', ['proyecto_id' => $proyecto->id_base_de_datos, 'modulo_id' => $modulo->id_modulo, 'tarea_id' => $tarea->id_tarea]) }}" 
                                class="btn btn-warning btn-sm" title="Editar Tarea">
                                    <i class="bi bi-pencil"></i> Editar
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center text-muted">
                                    <i class="bi bi-inbox fs-1 mb-2"></i>
                                    <p class="mb-0">Este alumno no tiene tareas registradas en este módulo.</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


@endsection