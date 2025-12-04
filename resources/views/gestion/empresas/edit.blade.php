@extends('layouts.default')

@extends('gestion.layouts.header')

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

            // Para modificar los cupos de forma activa
            $('.input-cupo').on('change', function() {
                var input = $(this);
                var periodo = input.data('periodo');
                // Si el valor está vacío o no es número, enviamos 0
                var plazas = input.val() === '' ? 0 : input.val(); 
                
                // Generamos la URL usando la ruta de Laravel, reemplazando el placeholder después
                // Esto evita errores si tu app está en una subcarpeta
                var urlUpdate = "{{ route('gestion.empresas.updateCupo', ':id') }}";
                urlUpdate = urlUpdate.replace(':id', "{{ $empresa->id_empresa }}");
                
                var statusSpan = $('#status-msg-' + periodo);

                // Feedback visual: guardando
                input.removeClass('border-success border-danger').addClass('border-warning');
                statusSpan.html('<span class="text-warning small"><i class="bi bi-hourglass-split"></i> Guardando...</span>');

                $.ajax({
                    url: urlUpdate, 
                    method: 'PUT',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}' // Cabecera de seguridad estándar
                    },
                    data: {
                        periodo: periodo,
                        plazas: plazas
                    },
                    success: function(response) {
                        // Éxito
                        input.removeClass('border-warning').addClass('border-success');
                        statusSpan.html('<span class="text-success small fw-bold"><i class="bi bi-check-circle-fill"></i> Guardado</span>');
                        
                        setTimeout(function() {
                            input.removeClass('border-success');
                            statusSpan.fadeOut(500, function() { $(this).html('').show(); });
                        }, 2000);
                    },
                    error: function(xhr) {
                        // Error
                        input.removeClass('border-warning').addClass('border-danger');
                        statusSpan.html('<span class="text-danger small fw-bold"><i class="bi bi-exclamation-triangle-fill"></i> Error</span>');
                        
                        // ALERTA DE DEPURACIÓN: Esto te dirá exactamente qué pasa
                        var msg = "Error desconocido";
                        if(xhr.responseJSON && xhr.responseJSON.message) {
                            msg = xhr.responseJSON.message;
                        } else if (xhr.status === 404) {
                            msg = "Ruta no encontrada (404). Verifica web.php/gestion_administracion.php";
                        } else if (xhr.status === 419) {
                            msg = "Sesión caducada (419). Recarga la página.";
                        } else if (xhr.status === 500) {
                            msg = "Error interno del servidor (500).";
                        }
                        
                        console.error("Error AJAX:", xhr);
                        alert("Fallo al guardar: " + msg);
                    }
                });
            });
        });
    </script>
@endsection

@section('content')
<div class="container my-5">
    <h2 class="texto mb-4">Editar Empresa: <span class="texto">{{ $empresa->nombre }}</span></h2>
    
    @if ($errors->any())
        <div class="alert alert-danger shadow-sm">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('gestion.empresas.update', $empresa->id_empresa) }}">
        @csrf
        @method('PUT') 
        
        <div class="card shadow-sm p-4 mb-5 border-0 bg-white">
            <h3 class="h5 border-bottom pb-2 mb-4">Datos Generales</h3>
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="nombre" class="form-label fw-bold">Nombre de la Empresa</label>
                    <input type="text" name="nombre" class="form-control" value="{{ old('nombre', $empresa->nombre) }}" required>
                </div>
                <div class="col-md-6">
                    <label for="cif_nif" class="form-label fw-bold">CIF/NIF</label>
                    <input type="text" name="cif_nif" class="form-control bg-light" value="{{ $empresa->cif_nif }}" readonly>
                </div>
                <div class="col-md-6">
                    <label for="nombre_gerente" class="form-label fw-bold">Nombre del Gerente</label>
                    <input type="text" name="nombre_gerente" class="form-control" value="{{ old('nombre_gerente', $empresa->nombre_gerente) }}">
                </div>
                <div class="col-md-6">
                    <label for="nif_gerente" class="form-label fw-bold">NIF del Gerente</label>
                    <input type="text" name="nif_gerente" class="form-control" value="{{ old('nif_gerente', $empresa->nif_gerente) }}">
                </div>
            </div>
        </div>

        {{-- SECCIÓN CUPOS --}}
        <div class="card shadow-sm p-4 mb-5 border-left-primary border-0">
            <h3 class="h5 border-bottom pb-2 mb-4">Disponibilidad de Plazas</h3>
            <div class="row g-4">
                {{-- 1º PERIODO --}}
                <div class="col-md-6">
                    <label class="form-label fw-bold text-secondary d-block">
                        <span class="badge bg-primary me-2">1º</span> Periodo
                    </label>
                    <div class="input-group">
                        {{-- IMPORTANTE: Clase 'input-cupo' y data-periodo='1' para AJAX --}}
                        <input type="number" 
                               name="plazas[1]" 
                               class="form-control text-center fw-bold input-cupo" 
                               data-periodo="1"
                               min="0"
                               placeholder="0"
                               value="{{ $empresa->cupos->where('periodo', '1')->first()->plazas ?? 0 }}">
                        <span class="input-group-text bg-light text-muted">alumnos</span>
                    </div>
                    <div id="status-msg-1" class="mt-1" style="height: 20px;"></div>
                </div>

                {{-- 2º PERIODO --}}
                <div class="col-md-6">
                    <label class="form-label fw-bold text-secondary d-block">
                        <span class="badge bg-secondary me-2">2º</span> Periodo
                    </label>
                    <div class="input-group">
                        {{-- IMPORTANTE: Clase 'input-cupo' y data-periodo='2' para AJAX --}}
                        <input type="number" 
                               name="plazas[2]" 
                               class="form-control text-center fw-bold input-cupo" 
                               data-periodo="2"
                               min="0" 
                               placeholder="0"  
                               value="{{ $empresa->cupos->where('periodo', '2')->first()->plazas ?? 0 }}">
                        <span class="input-group-text bg-light text-muted">alumnos</span>
                    </div>
                    <div id="status-msg-2" class="mt-1" style="height: 20px;"></div>
                </div>
            </div>
            
            {{-- Botones principales --}}
            <div class="d-flex justify-content-end gap-2 mt-4 pt-3">
                <a href="{{ route('gestion.empresas.index') }}" class="btn btn-secondary fw-bold">Volver</a>
                <button type="submit" class="btn btn-primary fw-bold px-4">Guardar Todo</button>
            </div>
        </div>
    </form>

    {{-- SECCIÓN TUTORES --}}
    <div class="card shadow-sm p-4 border-0">
        <div class="d-flex border-bottom justify-content-between align-items-center mb-4">
            <h3 class="h5 m-0">Tutores Laborales</h3>
            <a href="{{ route('gestion.tutores.create', ['empresa_id' => $empresa->id_empresa]) }}" class="btn btn-success btn-sm fw-bold">
                <i class="bi bi-plus-lg m-1"></i> Añadir Tutor
            </a>
        </div>
        
        <table id="tutores-datatable" class="table table-striped table-hover table-bordered w-100">
            <thead class="table-light">
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th class="text-center" style="width: 150px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($empresa->tutores as $tutor)
                    <tr>
                        <td class="align-middle">{{ $tutor->nombre }}</td>
                        <td class="align-middle">{{ $tutor->email }}</td>
                        <td class="text-center">
                            <a href="{{ route('gestion.tutores.edit', ['tutor_id' => $tutor->id_tutor_laboral]) }}" 
                               class="btn btn-sm btn-warning shadow-sm me-1">
                                <i class="bi bi-pencil-square"></i>Editar
                            </a>
                            <form action="{{ route('gestion.tutores.destroy', ['tutor_id' => $tutor->id_tutor_laboral]) }}" 
                                  method="POST" 
                                  class="d-inline"
                                  onsubmit="return confirm('¿Eliminar tutor y usuario? Irreversible.');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger shadow-sm">
                                    <i class="bi bi-trash-fill"></i>Eliminar
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