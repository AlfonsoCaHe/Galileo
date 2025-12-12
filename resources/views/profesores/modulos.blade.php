@extends('layouts.default')

@push('scripts')
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
                { "orderable": false, "targets": 3 } 
            ]
        });
    });
</script>
@endpush

@section('content')
<div class="container-fluid">
    @if(auth()->user()->isProfesor())
        @include('profesores.layouts.header')
    @endif
    <div class="row mb-4 mt-4">
        <div class="col-12">
            <h2 class="fw-bold texto">Módulos</h2>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tablaModulos" class="table table-striped table-hover dt-responsive nowrap" style="width:100%">
                    <thead>
                        <tr>
                            <th>Proyecto (BD)</th>
                            <th>Módulo</th>
                            <th class="text-center">Alumnos</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody><?php ?>
                        @foreach($modulos as $modulo)
                        <tr>
                            <td>
                                <span class="badge bg-secondary">{{ $modulo->nombre_proyecto }}</span>
                            </td>
                            <td class="fw-bold">{{ $modulo->nombre }}</td>
                            <td class="text-center">
                                <span class="badge bg-primary rounded-pill">{{ $modulo->alumnos_count }}</span>
                            </td>
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