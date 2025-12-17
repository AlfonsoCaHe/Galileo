@extends('layouts.default')

@section('scripts')
<script>
    $(document).ready(function() {
        $('#tablaModulos').DataTable({
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
        // 2. Filtro de unidad
        $(document).on('change', '#filtro-unidad', function() {
            var valor = $(this).val();

            // Apuntamos a la tabla
            var table = $('#tablaModulos').DataTable();

            if (valor) {
                // Busca el valor de la columna 1 (Unidad)
                table.column(1).search(valor).draw();
            } else {
                // Si el select está vacío, limpia el filtro y redibuja
                table.column(1).search('').draw();
            }
        });
    });
</script>
@endsection

@section('content')
<div class="container-fluid">
    @if(auth()->user()->isProfesor())
        @include('profesores.layouts.header')
    @endif

    @php
        $unidades = $modulos->pluck('unidad')->unique()->filter()->sort();// Extraemos las unidades únicas, eliminamos nulos y ordenamos alfabéticamente
    @endphp

    <div class="row mb-4 mt-4">
        <div class="col-12">
            <h2 class="fw-bold texto">Módulos</h2>
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

    <div class="card shadow-sm">
        <div class="card-body">
            {{-- Filtro de Unidades --}}
            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="filtro-unidad" class="form-label fw-bold">Filtrar por Unidad:</label>
                    <select id="filtro-unidad" class="form-select shadow-sm">
                        <option value="">Todas las unidades</option>
                        @foreach($unidades as $unidad)
                            <option value="{{ $unidad }}">{{ $unidad }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table id="tablaModulos" class="table table-striped table-hover dt-responsive nowrap w-100">
                    <thead>
                        <tr>
                            <th class="text-start">Módulo</th>
                            <th class="text-center">Unidad</th>
                            <th class="text-center">Proyecto (BD)</th>
                            <th class="text-center">Alumnos</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody><?php ?>
                        @foreach($modulos as $modulo)
                        <tr>
                            {{-- 0. Nombre módulo --}}
                            <td class="fw-bold">{{ $modulo->nombre }}</td>

                            {{-- 1. Unidad --}}
                            <td class="text-success">{{ $modulo->unidad }}</td>

                            {{-- 2. Nombre proyecto --}}
                            <td>
                                <span class="badge bg-secondary">{{ $modulo->nombre_proyecto }}</span>
                            </td>

                            {{-- 3. Conteo de alumnos --}}
                            <td class="text-center">
                                <span class="badge bg-primary rounded-pill">{{ $modulo->alumnos_count }}</span>
                            </td>

                            {{-- 4. Acciones --}}
                            <td class="text-center">
                                <div class="btn-group gap-2" role="group">

                                    {{-- Botón Ver RAs --}}
                                    <a href="{{ route('gestion.ras.index', ['proyecto_id' => $modulo->id_proyecto_galileo, 'modulo_id' => $modulo->id_modulo]) }}" 
                                       class="btn btn-info btn-sm text-white" 
                                       title="Ver Resultados de Aprendizaje">
                                        <i class="fas fa-list-check"></i> Ver RAs
                                    </a>

                                    {{-- Botón Ver Alumnos --}}
                                    <a href="{{ route('profesores.modulos.alumnos', ['proyecto_id' => $modulo->id_proyecto_galileo, 'modulo_id' => $modulo->id_modulo]) }}" 
                                       class="btn btn-info btn-sm text-white" 
                                       title="Ver Alumnos">
                                        <i class="fas fa-users"></i> Ver Alumnos
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