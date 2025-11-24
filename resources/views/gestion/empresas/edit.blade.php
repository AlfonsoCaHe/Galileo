@extends('layouts.default')

@section('scripts')
    <script>
        $(document).ready(function() {
            // Inicialización de la tabla de tutores anidada
            $('#tutores-datatable').DataTable({
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
                "paging": true,
                "columnDefs": [
                    { "orderable": false, "targets": 2 } 
                ]
            });
        });
    </script>
@endsection

@section('content')
<div class="container my-5">
    <h2>Editar Empresa: {{ $empresa->nombre }}</h2>
    
    @if ($errors->any())
        @endif

    <form method="POST" action="{{ route('gestion.empresas.update', $empresa->id_empresa) }}">
        @csrf
        @method('PUT') <h3>Datos de la Empresa</h3>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="nombre" class="form-label">Nombre de la Empresa</label>
                <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $empresa->nombre) }}" required>
            </div>
            <div class="col-md-6 mb-3">
                <label for="cif_nif" class="form-label">CIF/NIF</label>
                <input type="text" name="cif_nif" class="form-control" value="{{ $empresa->cif_nif }}" disabled >
            </div>
        </div>
        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="nombre_gerente" class="form-label">Nombre del Gerente</label>
                <input type="text" name="nombre_gerente" class="form-control" value="{{ old('nombre_gerente', $empresa->nombre_gerente) }}">
            </div>
            <div class="col-md-6 mb-3">
                <label for="nif_gerente" class="form-label">NIF del Gerente</label>
                <input type="text" name="nif_gerente" class="form-control" value="{{ old('nif_gerente', $empresa->nif_gerente) }}">
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg mt-4">Guardar Cambios</button>
        <a href="{{ route('gestion.empresas.index') }}" class="btn btn-secondary btn-lg mt-4">Volver al Listado</a>
    </form>

    <hr class="my-5">

    <h3>Tutores Laborales Asociados</h3>
    <a href="{{ route('gestion.tutores.create', ['empresa_id' => $empresa->id_empresa]) }}" class="btn btn-success mb-3 float-end">Añadir Nuevo Tutor</a>
    
    <table id="tutores-datatable" class="table table-striped table-bordered" style="width:100%">
        <thead>
            <tr>
                <th>Nombre del Tutor</th>
                <th>Email</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($empresa->tutores as $tutor)
                <tr>
                    <td>{{ $tutor->nombre }}</td>
                    <td>{{ $tutor->email }}</td>
                    <td>
                        <a href="{{ route('gestion.tutores.edit', ['tutor_id' => $tutor->id_tutor_laboral]) }}" 
                           class="btn btn-sm btn-warning">
                            Editar
                        </a>
                        <form action="{{ route('gestion.tutores.destroy', ['tutor_id' => $tutor->id_tutor_laboral]) }}" 
                              method="POST" 
                              style="display:inline-block;"
                              onsubmit="return confirm('¿Estás seguro de que deseas eliminar a este tutor y su usuario asociado? Esta acción es irreversible.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">
                                Eliminar
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection