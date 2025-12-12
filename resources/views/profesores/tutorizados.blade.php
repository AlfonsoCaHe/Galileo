@extends('layouts.default')

@push('scripts')
<script>
    $(document).ready(function() {
        var $table = $('#tablaTutorizados').DataTable({
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
            responsive: true,
            "columnDefs": [
                    { "orderable": false, "targets": [2, 4] } 
                ],
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
            <h2 class="fw-bold texto"><i class="bi bi-people-fill me-2"></i>Alumnos Tutorizados</h2>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tablaTutorizados" class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Alumno / Email</th>
                            <th>Módulos Matriculados</th>
                            <th>Tutor Laboral / Empresa</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($alumnosTutorizados as $alumno) 
                        <tr>
                            {{-- Datos de Alumno --}}
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-dark">{{ $alumno->nombre }}</span>
                                    <span class="text-muted small"><i class="bi bi-envelope me-1"></i>{{ $alumno->email }}</span>
                                    <span class="text-muted w-auto" style="width: fit-content;">
                                        Proyecto: {{ $alumno->proyecto_nombre }}
                                    </span>
                                </div>
                            </td>

                            {{-- Módulos matriculados --}}
                            <td>
                                <div class="d-flex flex-wrap gap-1">

                                    @foreach($alumno->modulos as $mod)
                                        <span class="badge bg-secondary text-white me-1">
                                            {{ $mod->nombre }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>

                            {{-- Tutor Laboral --}}
                            <td>
                                @if($alumno->tutor_laboral)
                                    <div class="d-flex flex-column">
                                        <span class="fw-bold text-dark">{{ $alumno->tutor_laboral->nombre }}</span>
                                        <span class="small text-muted">{{ $alumno->tutor_laboral->email }}</span>
                                        <span class="text-muted small">DNI: {{ $alumno->tutor_laboral->dni }}</span>
                                    </div>
                                @else
                                    <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> Sin asignar</span>
                                @endif
                            </td>

                            {{-- Acciones --}}
                            <td class="text-end">
                                <div class="dropdown">
                                    <a href="{{ route('profesores.tutorizados.tareas', ['proyecto_id' => $alumno->proyecto_id, 'alumno_id' => $alumno->id_alumno]) }}" class="btn btn-info btn-sm texto">
                                        <i class="bi bi-eye"></i> Ver Tareas
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-5 text-muted">
                                <i class="bi bi-emoji-neutral fs-1 mb-2"></i>
                                <p>No tienes alumnos asignados como tutor docente en los proyectos activos.</p>
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