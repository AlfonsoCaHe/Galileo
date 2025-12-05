@extends('layouts.default')

@extends('gestion.layouts.header')

@section('title', 'Gestión de Usuarios')

@section('scripts')
<script>
    $(document).ready(function() {
        
        // Inicialización de DataTables
        var table = $('#usuarios-datatable').DataTable({
            "language": {
                "decimal": ",",
                "emptyTable": "No hay usuarios registrados con el filtro seleccionado.",
                "info": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                "infoEmpty": "Mostrando 0 a 0 de 0 registros",
                "infoFiltered": "(filtrado de _MAX_ registros totales)",
                "lengthMenu": "Mostrar _MENU_ registros",
                "loadingRecords": "Cargando...",
                "processing": "Procesando...",
                "search": "Buscar:",
                "zeroRecords": "No se encontraron coincidencias",
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
            "processing": true,
            "serverSide": true,
            "ajax": {
                "url": "{{ route('gestion.usuarios.showDataTable') }}",
                "type": "POST",
                "data": function(d) {
                    d._token = "{{ csrf_token() }}";
                    // Enviamos el valor del select (activos / inactivos)
                    d.estado = $('#estado').val(); 
                }
            },
            "columns": [
                { data: 'id', name: 'id', visible: false }, 
                { data: 'name', name: 'name' }, 
                { data: 'email', name: 'email' },
                { data: 'rol', name: 'rol', className: "text-center" },
                // Nueva columna ESTADO (Switch)
                { data: 'estado', name: 'estado', orderable: false, searchable: false, className: "text-center" },
                // Columna Acciones (Solo Editar)
                { data: 'acciones', name: 'acciones', orderable: false, searchable: false, className: "text-center" }
            ]
        });

        // Recargar tabla al cambiar el filtro
        $('#estado').on('change', function() {
            table.ajax.reload();
        });
    });
</script>
@endsection

@section('content')
<div class="container-fluid">
    
    {{-- Cabecera --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0 texto">Gestión de Usuarios</h2>
        <a href="{{ route('admin.panel') }}" class="btn btn-danger shadow-sm">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>

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
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Tarjeta Principal --}}
    <div class="card shadow mb-4">
        
        {{-- Barra de Herramientas --}}
        <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
            
            {{-- Filtro de Estado --}}
            <div class="d-flex align-items-center">
                <label for="estado" class="form-label me-2 mb-0 fw-bold text-secondary">
                    <i class="bi bi-funnel-fill me-1"></i>Filtro:
                </label>
                <select name="estado" id="estado" class="form-select form-select-sm w-auto shadow-sm">
                    <option value="activos" selected>Usuarios Activos</option>
                    <option value="inactivos">Usuarios Inactivos</option>
                </select>
            </div>

            <h6 class="m-0 font-weight-bold text-primary d-none d-md-block">
                Listado de Usuarios del Sistema
            </h6>
        </div>
        
        <div class="card-body">
            <div class="table-responsive">
                <table id="usuarios-datatable" class="table table-bordered table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th>Id</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th class="text-center" style="width: 100px;">Rol</th>
                            <th class="text-center" style="width: 120px;">Estado</th>
                            <th class="text-center" style="width: 100px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- DataTables llena esto --}}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection