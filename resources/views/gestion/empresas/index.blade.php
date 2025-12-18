@extends('layouts.default')

@extends('gestion.layouts.header')

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
<div class="container-fluid my-5">
    <h1 class="m-4 texto">Gestión de Empresas y Tutores Laborales</h1>
    
    <div class="d-flex justify-content-end">
        <a href="{{ route('gestion.empresas.create') }}" class="btn btn-success m-2 fw-bold">
            Crear Nueva Empresa
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h4 class="alert-heading">¡Error al eliminar la Empresa!</h4>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

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
                    <th>Plazas</th>
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
                        
                        {{-- Plazas --}}
                        <td class="text-center">
                            <span class="badge rounded-pill bg-info text-dark border border-info p-2 px-3">
                                <i class="bi bi-people-fill me-1"></i> {{ $empresa->cupos_sum_plazas ?? 0 }}
                            </span>
                        </td>
                        {{-- Acciones --}}
                        <td>
                            <a href="{{ route('gestion.empresas.edit', ['empresa_id' => $empresa->id_empresa]) }}" 
                               class="btn btn-sm btn-info" 
                               title="Ver/Editar Empresa y sus Tutores">
                                Ver
                            </a>
                            
                            {{-- Eliminar Empresa --}}
                            <form action="{{ route('gestion.empresas.destroy', ['empresa_id' => $empresa->id_empresa]) }}" 
                                  method="POST" 
                                  style="display:inline-block;"
                                  onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta empresa y todos los tutores laborales asociados? Esta acción es irreversible y solo procederá si NINGÚN tutor tiene alumnos asociados.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" title="Eliminar Empresa">
                                    Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection