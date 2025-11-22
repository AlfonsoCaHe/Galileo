@extends('layouts.default')

@section('scripts')
    <script>
        $(document).ready(function() {
            // Inicialización de DataTables
            $('#empresas-datatable').DataTable({
                "language": {
                    "decimal": ",",
                    "emptyTable": "No hay datos en la tabla",
                    "info": "Monstrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                    "infoEmpty": "",
                    "infoFiltered": "",
                    "infoPostFix": "",
                    "thousands": ".",
                    "lengthMenu": "Mostrar _MENU_ registros",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Buscar:",
                    "zeroRecords": "No han encontrado registros",
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
                // Deshabilita la ordenación en la columna de Acciones (índice 6)
                "columnDefs": [
                    { "orderable": false, "targets": 6 } 
                ]
            });
        });
    </script>
@endsection

@section('content')
<div class="container my-5">
    <h2>Gestión de Empresas y Tutores Laborales</h2>
    
    <div class="d-flex">
        <a href="{{ route('gestion.empresas.create') }}" class="btn btn-success m-2">
            Crear Nueva Empresa
        </a>
        <a href="{{ route('home') }}" class="btn btn-secondary m-2">
            Panel Principal
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card p-3">
        <table id="empresas-datatable" class="table table-striped table-bordered" style="width:100%">
            <thead>
                <tr>
                    <th>Nombre de la Empresa</th>
                    <th>CIF/NIF</th>
                    <th>Nombre del Gerente</th>
                    <th>NIF del Gerente</th>
                    <th>Tutor Contacto</th>
                    <th>Email del Tutor</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                {{-- $empresas viene del método indexEmpresas() del controlador --}}
                @foreach ($empresas as $empresa)
                    @php
                        // Obtenemos el primer tutor laboral asociado (o null si no hay ninguno)
                        $tutor = $empresa->tutores->first();
                    @endphp
                    <tr>
                        {{-- Datos de la Empresa --}}
                        <td>{{ $empresa->nombre }}</td>
                        <td>{{ $empresa->cif_nif }}</td>
                        <td>{{ $empresa->nombre_gerente }}</td>
                        <td>{{ $empresa->nif_gerente }}</td>
                        
                        {{-- Datos del Tutor Principal --}}
                        <td>
                            @if ($tutor)
                                {{ $tutor->nombre }}
                            @else
                                <span class="badge bg-warning text-dark">Sin Tutor Asignado</span>
                            @endif
                        </td>
                        <td>{{ $tutor ? $tutor->email : 'N/A' }}</td>
                        
                        {{-- Acciones --}}
                        <td>
                            <a href="{{ route('gestion.empresas.edit', ['empresa_id' => $empresa->id_empresa]) }}" class="btn btn-sm btn-info" title="Editar Empresa y sus Tutores">
                                Ver
                            </a>
                            
                            <a href="{{ route('gestion.tutores.create', ['empresa_id' => $empresa->id_empresa]) }}" class="btn btn-sm btn-success" title="Añadir un nuevo tutor a esta empresa">
                                Tutor
                            </a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection