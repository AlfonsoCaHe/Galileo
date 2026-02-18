@extends('layouts.default')

@section('scripts')
<script>
    $(document).ready(function() {
        var $table = $('#tablaTutorizados').DataTable({
            "language": {
                "decimal": ",",
                "emptyTable": "No tienes alumnos asignados como tutor docente en los proyectos activos.",
                "info": "Mostrando _START_ a _END_ de _TOTAL_",
                "infoEmpty": "",
                "infoFiltered": "(filtrado)",
                "lengthMenu": "Mostrar _MENU_",
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
            responsive: true,
            autoWidth: false,
            columnDefs: [{
                    orderable: false,
                    targets: [1, 2, 3, 4]
                },
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
                    targets: [3, 4]
                }
            ]
        });

        // AJAX - ACTUALIZAR EL PERIODO DEL ALUMNO
        $('.select-periodo').on('change', function() {
            let periodo = $(this).val();
            let alumnoId = $(this).data('id');
            let proyectoId = $(this).data('proyecto');
            
            let $input = $(this); 
            let $indicator = $input.siblings('.status-indicator');

            // Feedback visual
            $input.removeClass('border-success border-danger').addClass('border-warning');
            // Si el indicador no existe (porque está dentro del componente), intentar buscarlo cerca o usar un toast
            if($indicator.length === 0) {
                 // Fallback por si el componente no renderiza el hermano directo
                 $indicator = $input.closest('td').find('.status-indicator');
            }
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
                        $indicator.fadeOut(500, function(){ 
                            $(this).html('').show(); 
                        });
                    }, 2000);
                },
                error: function(xhr) {
                    $input.removeClass('border-warning').addClass('border-danger');
                    $indicator.html('<span class="text-danger fw-bold">Error</span>');
                    console.error(xhr.responseText);
                    alert('Error al actualizar el periodo');
                    location.reload();
                }
            });
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
            <h2 class="fw-bold texto"><i class="bi bi-people-fill me-2"></i>Alumnos Tutorizados</h2>
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
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tablaTutorizados" class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Alumno / Email</th>
                            <th>Módulos Matriculados</th>
                            <th>Periodo</th>
                            <th>Tutor Laboral / Empresa</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($alumnosTutorizados as $alumno)
                        <tr>
                            {{-- 0. Datos de Alumno --}}
                            <td>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-dark">{{ $alumno->nombre }}</span>
                                    <span class="text-muted small"><i class="bi bi-envelope me-1"></i>{{ $alumno->email }}</span>
                                    <span class="text-muted w-auto" style="width: fit-content;">
                                        Proyecto: <span class="text-primary fw-bold">{{ $alumno->proyecto_nombre }}</span>
                                    </span>
                                </div>
                            </td>

                            {{-- 1. Módulos matriculados --}}
                            <td>
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($alumno->modulos as $mod)
                                    <span class="badge bg-secondary text-white me-1">
                                        {{ $mod->nombre }}
                                    </span>
                                    @endforeach
                                </div>
                            </td>

                            {{-- 2. Periodo --}}
                            <td>
                                <x-periodo-select 
                                    class="select-periodo" 
                                    data-id="{{ $alumno->id_alumno }}" 
                                    data-proyecto="{{ $alumno->proyecto_id }}"
                                    :selected="$alumno->periodo" 
                                />
                                {{-- Indicador visual para AJAX --}}
                                <div class="status-indicator small text-muted mt-1" style="min-height:20px;"></div>
                            </td>

                            {{-- 3. Tutor Laboral --}}
                            <td>
                                @if($alumno->tutor_laboral)
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-dark">{{ $alumno->tutor_laboral->nombre }}</span>
                                    <span class="small text-muted">{{ $alumno->tutor_laboral->email }}</span>
                                    <span class="text-muted small">DNI: {{ $alumno->tutor_laboral->dni }}</span>
                                </div>
                                @else
                                <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle"></i> Sin asignar</span>
                                @endif
                            </td>

                            {{-- 4. Acciones --}}
                            <td class="text-end">
                                <div class="dropdown">
                                    <a href="{{ route('profesores.tutorizados.tareas', ['proyecto_id' => $alumno->proyecto_id, 'alumno_id' => $alumno->id_alumno]) }}" class="btn btn-info btn-sm texto">
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
</div>
@endsection