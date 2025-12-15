@extends('layouts.default')

@section('title', 'Ficha del Alumno')

@section('scripts')
<script>
    $(document).ready(function() {
        // 1. DataTable Módulos
        $('#modulos-alumno-datatable').DataTable({
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
        });

        // 2. AJAX Tutor DOCENTE (Simple)
        $('#select-tutor-docente').change(function() {
            let tutorId = $(this).val();
            updateTutor("{{ route('gestion.alumnos.updateDocente', [$proyecto->id_base_de_datos, $alumno->id_alumno]) }}", tutorId);
        });

        // 3. AJAX Tutor LABORAL (Cascada)
        // A. Al cambiar empresa -> Cargar Tutores
        $('#select-empresa').change(function() {
            let empresaId = $(this).val();
            let $selectTutor = $('#select-tutor-laboral');

            $selectTutor.empty().append('<option value="">Cargando...</option>').prop('disabled', true);

            if (empresaId) {
                // Construimos la URL reemplazando el placeholder
                let url = "{{ route('gestion.alumnos.getTutoresEmpresa', [$proyecto->id_base_de_datos, ':id']) }}".replace(':id', empresaId);

                $.get(url, function(data) {
                    $selectTutor.empty();
                    $selectTutor.append('<option value="">--- Sin Tutor ---</option>');
                    $.each(data, function(index, tutor) {
                        $selectTutor.append('<option value="' + tutor.id_tutor_laboral + '">' + tutor.nombre + '</option>');
                    });
                    $selectTutor.prop('disabled', false);
                    $selectTutor.trigger('change');
                });
            } else {
                $selectTutor.empty().append('<option value="">Selecciona Empresa primero</option>');
            }
        });

        // B. Al cambiar tutor -> Guardar
        $('#select-tutor-laboral').change(function() {
            let tutorId = $(this).val();
            updateTutor("{{ route('gestion.alumnos.updateLaboral', [$proyecto->id_base_de_datos, $alumno->id_alumno]) }}", tutorId);
        });

        // Función genérica de guardado AJAX
        function updateTutor(url, id) {
            $.ajax({
                url: url,
                method: 'PUT',
                data: {
                    _token: "{{ csrf_token() }}",
                    tutor_id: id
                },
                success: function() {
                    alert('Asignación guardada correctamente.');
                },
                error: function() {
                    alert('Error al guardar la asignación.');
                }
            });
        }

        // AJAX - ACTUALIZAR EL PERIODO DEL ALUMNO
        $('.select-periodo').on('change', function() {
            let periodo = $(this).val();
            let alumnoId = $(this).data('id');
            let proyectoId = $(this).data('proyecto');

            let $input = $(this);
            let $indicator = $input.siblings('.status-indicator');

            $input.removeClass('border-success border-danger').addClass('border-warning');
            $indicator.html('<span class="text-warning fw-bold">...</span>');

            $.ajax({
                url: "{{ route('alumnos.update.periodo', 'proyectoID') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    id_alumno: alumnoId,
                    proyecto_id: proyectoId,
                    periodo: periodo
                },
                success: function(response) {
                    $input.removeClass('border-warning').addClass('border-success');
                    $indicator.html('<span class="text-success fw-bold"><i class="bi bi-check-lg"></i></span>');

                    setTimeout(function() {
                        $input.removeClass('border-success');
                        $indicator.fadeOut(500, function() {
                            $(this).html('').show();
                        });
                    }, 2000);
                },
                error: function(xhr) {
                    $input.removeClass('border-warning').addClass('border-danger');
                    $indicator.html('<span class="text-danger fw-bold">Error</span>');

                    alert(xhr.responseText);
                    location.reload();
                }
            });
        });
    });
</script>
@endsection

@section('content')
<div class="container-fluid">
    @if(auth()->user()->isAdmin())
        @include('gestion.layouts.header')
    @endif

    {{-- CABECERA --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-4 mt-4 texto">Ficha del Alumno</h2>
            <p class="mb-0 mt-1 texto">Proyecto: <strong class="text-warning">{{ $proyecto->proyecto }}</strong></p>
        </div>
        <a href="javascript:history.back()" class="btn btn-danger shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
        </a>
    </div>

    {{-- Bloque de errores --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h4 class="alert-heading">¡Error al eliminar el Proyecto!</h4>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- 1. DATOS PERSONALES --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3 text-primary">
            <h6 class="m-0 font-weight-bold"><i class="bi bi-person-badge me-2"></i>Datos Personales</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <label class="small text-muted fw-bold">Nombre Completo</label>
                    <p class="h5 text-dark">{{ $alumno->nombre }}</p>
                </div>
                <div class="col-md-4">
                    <label class="small text-muted fw-bold">Correo Electrónico</label>
                    <p class="text-dark">{{ $alumno->user->email }}</p>
                </div>
                <div class="col-md-2 text-center">
                    <label class="small text-muted fw-bold">Estado</label><br>
                    <span class="badge bg-success">ACTIVO</span>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. ASIGNACIÓN DE TUTORÍA (Selectores AJAX) --}}
    <div class="row">
        {{-- TUTOR DOCENTE --}}
        <div class="col-md-6 mb-4">
            <div class="card shadow h-100 border-left-info">
                <div class="card-header py-3 bg-white">
                    <h6 class="m-0 font-weight-bold text-primary">Tutoría Docente</h6>
                </div>
                <div class="card-body">
                    <label class="form-label">Profesor Responsable</label>
                    <select id="select-tutor-docente" class="form-select">
                        <option value="">-- Sin asignar --</option>
                        @foreach($profesores as $prof)
                        <option value="{{ $prof->id_profesor }}"
                            {{ $alumno->tutor_docente_id == $prof->id_profesor ? 'selected' : '' }}>
                            {{ $prof->nombre }}
                        </option>
                        @endforeach
                    </select>
                    <small class="text-muted mt-2 d-block"><i class="bi bi-info-circle me-1"></i>Se guarda automáticamente al cambiar.</small>
                </div>
            </div>
        </div>

        {{-- TUTOR LABORAL (DESPLEGABLE EN CASCADA) --}}
        <div class="col-md-6 mb-4">
            <div class="card shadow h-100 border-left-warning">
                <div class="card-header py-3 bg-white">
                    <h6 class="m-0 font-weight-bold text-primary">Tutoría Laboral</h6>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        {{-- 1. Select Empresa --}}
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">1. Empresa</label>
                            <select id="select-empresa" class="form-select form-select-sm">
                                <option value="">Seleccionar Empresa...</option>
                                @foreach($empresas as $emp)
                                <option value="{{ $emp->id_empresa }}"
                                    {{ ($alumno->tutorLaboral && $alumno->tutorLaboral->empresa_id == $emp->id_empresa) ? 'selected' : '' }}>
                                    {{ $emp->nombre }}
                                </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- 2. Select Tutor Laboral (Dinámico) --}}
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">2. Tutor Asignado</label>
                            <select id="select-tutor-laboral" class="form-select form-select-sm" {{ $alumno->tutor_laboral_id ? '' : 'disabled' }}>
                                <option value="" selected>--- Sin Tutor ---</option>
                                @if($alumno->tutor_laboral_id)
                                    <option value="{{ $alumno->tutor_laboral_id }}" selected>{{ $alumno->tutorLaboral->nombre }}</option>
                                @else
                                    <option value="">Selecciona empresa primero</option>
                                @endif
                            </select>
                        </div>

                        {{-- 3. Periodo de prácticas --}}
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">3. Periodo de prácticas</label>
                            <x-periodo-select
                                class="select-periodo"
                                data-id="{{ $alumno->id_alumno }}"
                                data-proyecto="{{ $proyecto_id }}"
                                :selected="$alumno->periodo" 
                                />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- 3. MÓDULOS MATRICULADOS --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Módulos Matriculados</h6>
            <button class="btn btn-sm btn-success shadow-sm" data-bs-toggle="modal" data-bs-target="#modalMatricular">
                <i class="bi bi-plus-circle me-1"></i> Matricular en Módulos
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="modulos-alumno-datatable" width="100%">
                    <thead class="table-light">
                        <tr>
                            <th>Módulo / Asignatura</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($alumno->modulos as $mod)
                        <tr>
                            <td>{{ $mod->nombre }}</td>
                            <td class="text-center">
                                <form action="{{ route('gestion.alumnos.desmatricular', ['proyecto_id' => $proyecto->id_base_de_datos, 'alumno_id' => $alumno->id_alumno, 'modulo_id' => $mod->id_modulo]) }}"
                                    method="POST"
                                    onsubmit="return confirm('¿Dar de baja de {{ $mod->codigo }}? Pasará al historial.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger shadow-sm">
                                        <i class="bi bi-trash-fill"></i>Desmatricular
                                    </button>
                                </form>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- MODAL DE MATRICULACIÓN (Solo formulario de alta) --}}
<div class="modal fade" id="modalMatricular" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('gestion.alumnos.matricular', [$proyecto->id_base_de_datos, $alumno->id_alumno]) }}" method="POST">
            @csrf
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Matricular en nuevos módulos</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if($modulosDisponibles->isEmpty())
                    <p class="text-center text-muted">El alumno ya está matriculado en todos los módulos disponibles.</p>
                    @else
                    <p class="small text-muted">Selecciona los módulos a añadir:</p>
                    <div class="list-group">
                        @foreach($modulosDisponibles as $m)
                        <label class="list-group-item">
                            <input class="form-check-input me-1" type="checkbox" name="modulos[]" value="{{ $m->id_modulo }}">
                            <strong>{{ $m->codigo }}</strong> - {{ $m->nombre }}
                        </label>
                        @endforeach
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    @if(!$modulosDisponibles->isEmpty())
                    <button type="submit" class="btn btn-success">Guardar</button>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

{{-- 4. HISTORIAL DE BAJAS (PAPELERA) --}}
{{-- Esto va en la página principal, NO dentro del modal --}}
@if($alumno->modulosBorrados->count() > 0)
<div class="card shadow mb-4 border-left-danger mt-4">
    <div class="card-header py-3 bg-white">
        <h6 class="m-0 font-weight-bold text-danger">
            <i class="bi bi-trash-fill me-2"></i>Módulos Desmatriculados (Recuperables)
        </h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-sm align-middle">
                <thead class="table-light text-danger">
                    <tr>
                        <th>Módulo</th>
                        <th class="text-center">Fecha de Baja</th>
                        <th class="text-center">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($alumno->modulosBorrados as $mod)
                    <tr>
                        <td>{{ $mod->nombre }}</td>
                        <td class="text-center">
                            {{ \Carbon\Carbon::parse($mod->pivot->deleted_at)->format('d/m/Y H:i') }}
                        </td>
                        <td class="text-center">
                            {{-- FORMULARIO DE RESTAURACIÓN --}}
                            <form action="{{ route('gestion.alumnos.restaurar', ['proyecto_id' => $proyecto->id_base_de_datos, 'alumno_id' => $alumno->id_alumno, 'modulo_id' => $mod->id_modulo]) }}"
                                method="POST">
                                @csrf
                                @method('PUT') {{-- Importante: Método PUT --}}

                                <button type="submit" class="btn btn-sm btn-success shadow-sm fw-bold">
                                    <i class="bi bi-arrow-counterclockwise me-1"></i> Restaurar
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

@endsection