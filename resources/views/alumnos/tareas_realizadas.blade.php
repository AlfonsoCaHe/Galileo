@extends('layouts.default')
@include('alumnos.layouts.header')

@section('scripts')
    <script>
        $(document).ready(function() {
            $('#tablaTareas').DataTable({
                responsive: true,
                autoWidth: false,
                "language": {
                    "decimal": ",",
                    "emptyTable": "No hay tareas finalizadas.",
                    "info": "Mostrando _START_ a _END_ de _TOTAL_ registros",
                    "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                    "infoFiltered": "(filtrado de _MAX_ registros totales)",
                    "lengthMenu": "Mostrar _MENU_ registros",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Buscar:",
                    "zeroRecords": "No se encontraron resultados",
                    "paginate": {
                        "first": "Primero",
                        "last": "Último",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    }
                },
                columnDefs: [
                    // 1. DESHABILITAR ORDENAMIENTO (Duración y Calificación)
                    { orderable: false, targets: [4, 5] },
                    
                    // 2. CENTRADO VERTICAL
                    { className: "align-middle", targets: "_all" },
                    
                    // 3. PRIORIDAD RESPONSIVE
                    // Mantenemos Tarea (1) y Calificación (5) visibles siempre que se pueda
                    { responsivePriority: 1, targets: 1 },
                    { responsivePriority: 2, targets: 5 }
                ]
            });
        });
    </script>
@endsection

@section('content')
<div class="container-fluid py-4">
    
    {{-- Título y Botón Volver --}}
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3 bg-light p-3 rounded">
        <h2 class="fw-bold text-dark m-0">Tareas Finalizadas</h2>
        <div>
            <a href="{{ route('alumnos.panel') }}" class="btn btn-danger fw-bold">
                <i class="bi bi-arrow-return-left"></i> <span class="d-md-inline">Volver</span>
            </a>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="card shadow border-0">
        <div class="card-body">
            <table id="tablaTareas" class="table table-striped table-hover dt-responsive nowrap w-100">
                <thead class="table-light">
                    <tr>
                        <th class="text-start">Descripción</th>
                        <th class="none">Tarea</th>{{-- Oculto, sale en el + --}}
                        <th class="text-start">Módulo</th>
                        <th class="text-center">Fecha</th>
                        <th class="text-center">Duración</th>
                        <th class="text-center">Calificación</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tareasRealizadas as $tarea)
                    <tr>
                        {{-- 0. Descripción (notas_alumno) --}}
                        <td class="text-muted">
                            <div class="p-2 bg-light border rounded">
                                {{ $tarea->notas_alumno ?: 'Sin anotaciones' }}
                            </div>
                        </td>

                        {{-- 1. Tarea --}}
                        <td class="fw-bold text-primary">
                            <span class="text-primary">{{ $tarea->nombre_tarea }}</span>
                        </td>

                        {{-- 2. Módulo --}}
                        <td>
                            {{ $tarea->nombre_modulo }}
                        </td>

                        {{-- 3. Fecha --}}
                        <td class="text-center">
                            {{ \Carbon\Carbon::parse($tarea->fecha)->format('d/m/Y') }}
                        </td>

                        {{-- 4. Duración --}}
                        <td class="text-center">
                            {{ $tarea->duracion ?? '-' }}
                        </td>

                        {{-- 5. Calificación --}}
                        <td class="text-center">
                            <div class="d-flex align-items-center justify-content-center gap-2">
                                <span class="fw-bold {{ $tarea->apto >= 5 ? 'text-success' : 'text-danger' }}">
                                    {{ $tarea->apto >= 5 ? 'Apto' : 'No Apto' }}
                                </span>
                            </div>
                        </td>

                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection