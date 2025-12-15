@extends('layouts.default')

@section('scripts')
<script>
    $(document).ready(function() {
        var $table = $('#tablaTutorizados').DataTable({
            "language": {
                "decimal": ",",
                "emptyTable": "No hay tareas registradas.",
                "info": "Mostrando _START_ a _END_ de _TOTAL_",
                "infoEmpty": "",
                "infoFiltered": "(filtrado)",
                "lengthMenu": "Mostrar _MENU_",
                "loadingRecords": "Cargando...",
                "processing": "Procesando...",
                "search": "Buscar:",
                "zeroRecords": "No hay coincidencias",
                "paginate": {
                    "first": "First",
                    "last": "Last",
                    "next": "Next",
                    "previous": "Prev"
                }
            },
            responsive: true,
            autoWidth: false,
            columnDefs: [{
                    orderable: false,
                    targets: [3]
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
                    targets: [3]
                }
            ]
        });
    });
</script>
@endsection

@section('content')
@if(auth()->user()->isTutorLaboral())
    @include('tutores_laborales.layouts.header')
@endif
<div class="container-fluid py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold texto"><i class="bi bi-people-fill me-2"></i>Alumnos</h2>
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
        <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\" aria-label=\"Close\"></button>
    </div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-body">
            <div class="table-responsive">
                <table id="tablaTutorizados" class="table table-hover align-middle ">
                    <thead class="table-light">
                        <tr>
                            <th class="text-center">Alumno</th>
                            <th class="text-center">email</th>
                            <th class="text-center">Profesor</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($alumnosTutorizados as $alumno)
                        <tr>
                            {{-- 0. Nombre de Alumno --}}
                            <td class="text-center">
                                <span class="text-dark">{{ $alumno->nombre }}</span>
                            </td>
                            {{-- 1. Email --}}
                            <td class="text-center">
                                <span class="text-dark"><i class="bi bi-envelope me-1"></i>{{ $alumno->email }}</span>
                            </td>

                            {{-- 2. Profesor --}}
                            <td class="text-center">
                                <span class="text-dark">{{ $alumno->profesor }}</span>
                            </td>

                            {{-- 3. Acciones --}}
                            <td class="text-center">
                                <div class="dropdown">
                                    <a href="{{ route('tutores_laborales.tareas.alumno', ['proyecto_id' => $alumno->proyecto_id, 'alumno_id' => $alumno->id_alumno]) }}" class="btn btn-info btn-sm texto">
                                        <i class="bi bi-eye"></i> Ver Tareas
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center py-5 text-muted">
                                <i class="bi bi-emoji-neutral fs-1 mb-2"></i>
                                <p>Actualmente no tienes alumnos asignados como tutor laboral.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>


@endsection