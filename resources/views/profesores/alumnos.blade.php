@extends('layouts.default')

@push('scripts')
<script>
    $(document).ready(function() {
        $('#tablaAlumnos').DataTable({
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
            responsive: true
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
            <h2 class="fw-bold texto">Alumnado del Módulo: <span class="text-warning">{{ $modulo->nombre }}</span></h2>
            <p class="texto">Proyecto: <span class="text-warning">{{ $proyecto->proyecto }}</span></p>
        </div>
        
        <div class="btn-group gap-2 me-2" role="group">
            <a href="{{ route('gestion.tareas.create', ['proyecto_id' => $proyecto->id_base_de_datos, 'modulo_id' => $modulo->id_modulo]) }}" 
               class="btn btn-success d-flex align-items-center">
                <i class="bi bi-plus-circle"></i> Crear Actividad
            </a>

            <a href="{{ route('profesores.modulos') }}" class="btn btn-danger d-flex align-items-center">
                <i class="bi bi-arrow-left-circle"></i> Volver
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tablaAlumnos" class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre del Alumno</th>
                            <th>Email</th>
                            <th class="text-center">Tareas Entregadas</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($alumnos as $alumno)
                        <tr>
                            <td class="fw-bold">{{ $alumno->nombre }}</td>
                            <td>{{ $alumno->email ?? 'Sin email' }}</td>
                            
                            <td class="text-center">
                                <span class="badge bg-secondary rounded-pill">
                                    {{ $alumno->tareas_count }}
                                </span>
                            </td>
                            
                            <td class="text-end">
                                <a href="{{ route('profesores.alumnos.tareas', ['proyecto_id' => $proyecto->id_base_de_datos, 'modulo_id' => $modulo->id_modulo, 'alumno_id' => $alumno->id_alumno]) }}" 
                                   class="btn btn-info btn-sm text-white" 
                                   title="Ver Tareas del Alumno">
                                    <i class="bi bi-eye"></i> Ver Tareas
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">
                                No hay alumnos matriculados en este módulo.
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