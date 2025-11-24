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

            // Lógica de confirmación de borrado
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
        });
    </script>
@endsection

@section('content')
<div class="container my-5">
    <h1 class="mb-4 text-primary">Listado de Alumnos</h1>

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
    <div class="card shadow-lg p-4">
        {{-- Botón para Añadir Nuevo Alumno --}}
        <div class="d-flex justify-content-start mb-3">
            <div class="d-flex">
                <a href="{{ route('gestion.alumnos.create') }}"class="btn btn-success fw-bold m-2">
                    Nuevo Alumno
                </a>
                <a href="{{ route('admin.panel') }}" class="btn btn-secondary fw-bold m-2">
                    Volver
                </a>
            </div>
        </div>

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
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($alumnos_totales as $alumno)
                    <tr>
                        <td>{{ $alumno->nombre }}</td>
                        <td>
                            <span class="badge bg-primary">{{ $alumno->proyecto_nombre }}</span>
                        </td>
                        <td>
                            {{-- 3. Tutor Docente (Profesor) --}}
                            {{ $alumno->tutorDocente->nombre ?? 'N/A' }}
                        </td>
                        <td>
                            {{-- 4. Tutor Laboral --}}
                            {{ $alumno->tutorLaboral->nombre ?? 'N/A' }}
                        </td>
                        <td>
                            {{-- 5. Acciones: Ver, Editar, Eliminar --}}

                            {{-- Ver Alumno (Usando la ruta del proyecto) --}}
                            <a href="{{ route('alumno.show', ['alumno_id' => $alumno->id_alumno]) }}" 
                               class="btn btn-sm btn-info text-white" title="Ver Detalles">
                                Ver
                            </a>

                            {{-- Editar Alumno --}}
                            <a href="{{ route('gestion.alumnos.edit', ['proyecto_id' => $alumno->proyecto_galileo_id, 'alumno_id' => $alumno->id_alumno]) }}" 
                               class="btn btn-sm btn-warning" title="Editar">
                                Editar
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