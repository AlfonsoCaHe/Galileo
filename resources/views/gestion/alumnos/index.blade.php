@extends('layouts.default')

@section('title', 'Listado de Alumnos')

@section('scripts')
    {{-- Asegúrate de incluir los scripts de DataTables --}}
    <script>
        $(document).ready(function() {
            // Inicialización de DataTables
            $('#alumnos-datatable').DataTable({
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
                "columnDefs": [
                    { "orderable": false, "targets": 4 } 
                ]
            });

            // Gestión de confirmación de borrado
            // 1. Selecciona todos los formularios con la clase 'delete-form'
            $('.delete-form').on('submit', function(e) {
                const form = $(this);
                
                // 2. Obtener el nombre del alumno para un mensaje personalizado (Opcional, pero muy útil)
                // Esto busca el texto de la primera celda (nombre del alumno) de la fila actual.
                const row = form.closest('tr');
                const alumnoNombre = row.find('td:eq(0)').text().trim(); 

                // 3. Mostrar el cuadro de diálogo de confirmación
                if (!confirm(`¿Estás seguro de que deseas ELIMINAR al alumno "${alumnoNombre}"? Esta acción es irreversible.`)) {
                    e.preventDefault(); // Detiene el envío del formulario si el usuario pulsa "Cancelar"
                }
            });

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
                            $indicator.fadeOut(500, function(){ 
                                $(this).html('').show(); 
                            });
                        }, 2000);
                    },
                    error: function(xhr) {
                        $input.removeClass('border-warning').addClass('border-danger');
                        $indicator.html('<span class="text-danger fw-bold">Error</span>');
                        
                        alert.error(xhr.responseText);
                        location.reload();
                    }
                });
            });
        });
    </script>
@endsection

@section('content')
@include('gestion.layouts.header')
<div class="container-fluid my-5">
    <h1 class="m-4 texto">Listado de Alumnos</h1>

    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <h4 class="alert-heading">¡Error en la Operación!</h4>
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
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    {{-- Menú de opciones --}}
    <div class="d-flex justify-content-end mb-3">
        <div class="d-flex">
            <a href="{{ route('gestion.alumnos.create') }}"class="btn btn-success fw-bold m-2">
                Nuevo Alumno
            </a>
        </div>
    </div>
    <div class="card shadow-lg p-4">

        @if($alumnos_totales->isEmpty())
            <p class="alert alert-warning">No hay alumnos registrados en la base de datos.</p>
        @else
            <table id="alumnos-datatable" class="table table-striped table-hover w-100">
                <thead>
                    <tr>
                        <th>Nombre del Alumno</th>
                        <th>Proyecto</th>
                        <th>Tutor Docente</th>
                        <th>Tutor Laboral</th>
                        <th>Periodo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($alumnos_totales as $alumno)
                    <tr>
                        {{-- 0. Nombre alumno --}}
                        <td>{{ $alumno->nombre }}</td>

                        {{-- 1. Proyecto --}}
                        <td>
                            <span class="badge bg-primary">{{ $alumno->proyecto_nombre }}</span>
                        </td>

                        {{-- 2. Tutor Docente --}}
                        <td>
                            {{-- 3. Tutor Docente (Profesor) --}}
                            {{ $alumno->tutorDocente->nombre ?? 'N/A' }}
                        </td>

                        {{-- 3. Tutor Laboral --}}
                        <td>
                            {{ $alumno->tutorLaboral->nombre ?? 'N/A' }}
                        </td>

                        {{-- 4. Periodo --}}
                        <td>
                            <x-periodo-select 
                                class="select-periodo" 
                                data-id="{{ $alumno->id_alumno }}" 
                                data-proyecto="{{ $alumno->proyecto_galileo_id }}"
                                :selected="$alumno->periodo" 
                            />
                        </td>

                        {{-- 5. Acciones: Ver, Editar, Eliminar --}}
                        <td>
                            {{-- Ver Alumno (Usando la ruta del proyecto) --}}
                            <a href="{{ route('gestion.alumnos.show', ['proyecto_id' => $alumno->proyecto_galileo_id, 'alumno_id' => $alumno->id_alumno]) }}" 
                               class="btn btn-sm btn-info text-white" title="Ver Detalles">
                                Ver
                            </a>

                            {{-- Editar Alumno --}}
                            <a href="{{ route('gestion.alumnos.edit', ['proyecto_id' => $alumno->proyecto_galileo_id, 'alumno_id' => $alumno->id_alumno]) }}" 
                               class="btn btn-sm btn-warning" title="Editar">
                                Datos Pers.
                            </a>

                            {{-- Formulario para Eliminar --}}
                            <form action="{{ route('gestion.alumnos.destroy', ['proyecto_id' => $alumno->proyecto_galileo_id, 'alumno_id' => $alumno->id_alumno]) }}" 
                                  method="POST" class="d-inline-block delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                    Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
@endsection