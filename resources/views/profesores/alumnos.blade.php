@extends('layouts.default')

@section('scripts')
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
                "zeroRecords": "No hay alumnos matriculados en este módulo.",
                "paginate": {
                    "first": "First",
                    "last": "Last",
                    "next": "Next",
                    "previous": "Prev"
                }
            },
            "responsive": true,
            "pageLength": 10,
            "columnDefs": [
                // Desactivamos ordenación de acciones
                {
                    orderable: false,
                    targets: 3
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
                    targets: 4
                }
            ]
        });

        $('#tablaActividades').DataTable({
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
                "zeroRecords": "No hay tareas realizadas en este módulo.",
                "paginate": {
                    "first": "First",
                    "last": "Last",
                    "next": "Next",
                    "previous": "Prev"
                }
            },
            "responsive": true,
            "pageLength": 10,
            "columnDefs": [
                // Desactivamos ordenación de acciones
                {
                    orderable: false,
                    targets: 3
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
                    targets: [0, 1]
                },
                {
                    responsivePriority: 2,
                    targets: 4
                }
            ]
        });
    });
</script>
@endsection

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
            <a href="{{ route('gestion.actividades.create', ['proyecto_id' => $proyecto->id_base_de_datos, 'modulo_id' => $modulo->id_modulo]) }}"
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
                <table id="tablaAlumnos" class="table table-hover align-middle dt-responsive nowrap w-100">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start">Nombre del Alumno</th>
                            <th class="text-center">Email</th>
                            <th class="text-center">Periodo</th>
                            <th class="text-center">Tareas Realizadas</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($alumnos as $alumno)
                        <tr>
                            {{-- 0. Alumno --}}
                            <td class="fw-bold">{{ $alumno->nombre }}</td>

                            {{-- 1. email --}}
                            <td>{{ $alumno->email ?? 'Sin email' }}</td>

                            {{-- 2. Periodo --}}
                            <td>{{ $alumno->periodo }}</td>

                            {{-- 3. Cuenta de tareas del alumno --}}
                            <td class="text-center">
                                <span class="badge bg-secondary rounded-pill">
                                    {{ $alumno->tareas_count }}
                                </span>
                            </td>

                            {{-- 4. Acciones --}}
                            <td class="text-center">
                                <div class="row g-2 justify-content-center" style="width:clump(65, 50%, 100)">
                                    <a href="{{ route('profesores.alumnos.tareas', ['proyecto_id' => $proyecto->id_base_de_datos, 'modulo_id' => $modulo->id_modulo, 'alumno_id' => $alumno->id_alumno]) }}"
                                        class="btn btn-info btn-sm text-white"
                                        title="Ver Tareas del Alumno">
                                        <i class="bi bi-eye"></i> Ver Tareas
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="mt-4 mb-4">
        <h2 class="fw-bold texto">Actividades del módulo</h2>
    </div>
    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tablaActividades" class="table table-hover align-middle dt-responsive nowrap w-100">
                    <thead class="table-light">
                        <tr>
                            <th>Actividad</th>
                            <th>Tarea</th>
                            <th class="text-center">Descripción</th>
                            <th class="text-center">Tareas realizadas</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($actividades as $actividad)
                        <tr>
                            {{-- Nombre de la actividad --}}
                            <td class="fw-bold">{{ $actividad->nombre }}</td>

                            {{-- Tarea --}}
                            <td>{{ $actividad->tarea}}</td>

                            {{-- Descripción --}}
                            <td>{{ $actividad->descripcion}}</td>

                            {{-- Conteo de tareas que han realizado la actividad --}}
                            <td class="text-center">
                                <span class="badge bg-secondary rounded-pill">
                                    {{ $actividad->actividades_count ?? '0'}}
                                </span>
                            </td>

                            {{-- Acciones --}}
                            <td class="text-center">
                                <div class="row g-2 justify-content-center" style="min-width: 65px;">
                                    <a href="{{ route('gestion.actividades.edit', ['proyecto_id' => $proyecto->id_base_de_datos, 'modulo_id' => $modulo->id_modulo, 'actividad_id' => $actividad->id_actividad]) }}"
                                        class="btn btn-warning btn-sm text-white"
                                        title="Ver Actividad">
                                        <i class="bi bi-eye"></i> Editar
                                    </a>
                                    <a href="{{ route('gestion.actividades.destroy', ['proyecto_id' => $proyecto->id_base_de_datos, 'modulo_id' => $modulo->id_modulo, 'actividad_id' => $actividad->id_actividad]) }}"
                                        class="btn btn-danger btn-sm text-white"
                                        title="Eliminar Actividad">
                                        <i class="bi bi-eye"></i> Eliminar
                                    </a>
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