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
                "emptyTable": "No hay datos en la tabla",
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
                // 1. DESHABILITAR ORDENAMIENTO (Duración, Calificación, Acciones)
                {
                    orderable: false,
                    targets: [3, 4, 5]
                },

                // 2. CENTRADO VERTICAL
                {
                    className: "align-middle",
                    targets: "_all"
                },

                // 3. PRIORIDAD RESPONSIVE (Evita que desaparezcan en móvil)
                // 1 = Máxima prioridad (Tarea)
                // 2 = Alta prioridad (Acciones)
                {
                    responsivePriority: 1,
                    targets: 0
                },
                {
                    responsivePriority: 2,
                    targets: 5
                }
            ]
        });
    });
</script>
@endsection

@section('content')
<div class="container-fluid py-4">

    {{-- Título y Botones --}}
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3 bg-light p-3 rounded">
        <h2 class="fw-bold text-dark m-0">Tareas Sin Finalizar</h2>
        <div>
            <a href="{{ route('alumnado.createTarea', ['proyecto_id' => $proyecto->id_base_de_datos])}}" class="btn btn-success w-100 fw-bold me-2">
                <i class="bi bi-plus-lg"></i> <span class="d-md-inline">Crear Tarea</span>
            </a>
            <a href="{{ route('alumnos.panel') }}" class="btn btn-danger w-100 fw-bold">
                <i class="bi bi-arrow-return-left"></i> <span class="d-md-inline">Volver</span>
            </a>
        </div>
    </div>

    {{-- Mensajes de Feedback --}}
    @if ($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
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
        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
        <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>
    </div>
    @endif

    {{-- Tabla --}}
    <div class="card shadow border-0">
        <div class="card-body">
            <table id="tablaTareas" class="table table-striped table-hover dt-responsive nowrap w-100">
                <thead class="table-light">
                    <tr>
                        <th class="text-start">Descripción</th>
                        <th class="none">Tarea</th>{{-- Oculto, sale al pulsar + --}}
                        <th class="text-center">Fecha</th>
                        <th class="text-center">Duración</th>
                        <th class="text-center">Apto</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tareasDisponibles as $tarea)
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

                        {{-- 2. Fecha --}}
                        <td class="text-center">
                            {{ \Carbon\Carbon::parse($tarea->fecha)->format('d/m/Y') }}
                        </td>

                        {{-- 3. Duración --}}
                        <td class="text-center">
                            {{ $tarea->duracion ?? '-' }}
                        </td>

                        {{-- 4. Calificación (Badge en vez de checkbox deshabilitado) --}}
                        <td class="text-center">
                            @if($tarea->apto >= 5)
                            <span class="badge bg-success rounded-pill">
                                <i class="bi bi-check-lg"></i> Apto
                            </span>
                            @else
                            <span class="badge bg-danger rounded-pill">
                                <i class="bi bi-x-lg"></i> No Apto
                            </span>
                            @endif
                        </td>

                        {{-- 5. Acciones --}}
                        <td class="text-center">
                            {{--
         justify-content-center: Centra los botones cuando sobran espacios (en pantalla grande).
         min-width: 220px: Asegura que no se aplasten demasiado antes de que entre el modo responsive.
    --}}
                            <div class="row g-2 justify-content-center" style="min-width: 220px;">

                                {{--
            BOTÓN EDITAR 
            col-6: En móvil/tablet ocupa el 50% del ancho.
            col-xl-3: En pantalla grande (>1200px) ocupa el 25% del ancho.
        --}}
                                <div class="col-6 col-xl-3">
                                    <a href="{{ route('alumnado.editTarea', ['proyecto_id' => $proyecto->id_base_de_datos, 'modulo_id' => $tarea->modulo_id, 'tarea_id' => $tarea->id_tarea]) }}"
                                        class="btn btn-warning btn-sm w-100 fw-bold d-flex align-items-center justify-content-center"
                                        title="Editar">
                                        <i class="bi bi-pencil-square me-1"></i> Editar
                                    </a>
                                </div>

                                {{--
            BOTÓN ELIMINAR 
            Misma lógica: 50% en móvil, 25% en escritorio grande.
        --}}
                                <div class="col-6 col-xl-3">
                                    <form action="{{ route('alumnado.destroyTarea', ['proyecto_id' => $proyecto->id_base_de_datos, 'tarea_id' => $tarea->id_tarea]) }}"
                                        method="POST"
                                        class="w-100">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="btn btn-danger btn-sm w-100 fw-bold d-flex align-items-center justify-content-center"
                                            onclick="return confirm('¿Seguro que deseas eliminar esta tarea?')"
                                            title="Eliminar">
                                            <i class="bi bi-trash-fill me-1"></i> Eliminar
                                        </button>
                                    </form>
                                </div>

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