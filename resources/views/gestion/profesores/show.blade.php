@extends('layouts.default')

@section('title', 'Ficha del Profesor')

@section('scripts')
    <script>
        $(document).ready(function() {
            // Configuración común para ambas tablas
            const commonConfig = {
                "language": {
                    "url": "//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json"
                },
                "pageLength": 10
            };

            // Tabla 1: Módulos
            $('#tabla-modulos').DataTable({
                ...commonConfig,
                "columnDefs": [{ "orderable": false, "targets": 3 }] // Acciones
            });

            // Tabla 2: Alumnos Tutorados
            $('#tabla-alumnos').DataTable({
                ...commonConfig,
                "columnDefs": [{ "orderable": false, "targets": 2 }] // Acciones
            });
        });
    </script>
@endsection

@section('content')
@include('gestion.layouts.header')
<div class="container-fluid">

    {{-- CABECERA --}}
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h1 class="h3 mb-0 texto">Ficha del Profesor</h1>
        </div>
        <a href="{{ route('gestion.profesores.index') }}" class="btn btn-danger shadow-sm">
            <i class="bi bi-arrow-left me-1"></i>Volver
        </a>
    </div>

    <div class="row">
        
        {{-- COLUMNA IZQUIERDA: DATOS PERSONALES --}}
        <div class="col-xl-3 col-md-12 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Datos Personales</h6>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center mx-auto" style="width: 80px; height: 80px; font-size: 2rem;">
                            {{ strtoupper(substr($profesor->nombre, 0, 1)) }}
                        </div>
                        <h4 class="mt-3 font-weight-bold text-gray-800">{{ $profesor->nombre }}</h4>
                        <span class="badge {{ $profesor->activo ? 'bg-success' : 'bg-danger' }}">
                            {{ $profesor->activo ? 'ACTIVO' : 'INACTIVO' }}
                        </span>
                    </div>
                    
                    <hr>
                    
                    <div class="mb-3">
                        <small class="text-muted fw-bold text-uppercase">Correo Electrónico</small>
                        <p class="mb-0 text-dark">{{ optional($profesor->user)->email ?? 'Sin usuario asociado' }}</p>
                    </div>

                    <div class="d-grid mt-4">
                        <a href="{{ route('gestion.profesores.edit', $profesor->id_profesor) }}" class="btn btn-warning shadow-sm">
                            <i class="bi bi-pencil-square me-1"></i> Editar Datos
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- COLUMNA DERECHA: TABLAS DE GESTIÓN --}}
        <div class="col-xl-9 col-md-12">
            
            {{-- TABLA 1: MÓDULOS DOCENTES --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-white">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-journal-bookmark-fill me-2"></i>Módulos que imparte
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle w-100" id="tabla-modulos">
                            <thead class="table-light">
                                <tr>
                                    <th>Proyecto</th>
                                    <th>Módulo</th>
                                    <th class="text-center">Alumnos</th>
                                    <th class="text-center" style="width: 150px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($modulosDocentes as $modulo)
                                    <tr>
                                        <td><span class="badge bg-light text-dark border">{{ $modulo->proyecto_nombre }}</span></td>
                                        <td class="fw-bold text-primary">{{ $modulo->nombre }}</td>
                                        <td class="text-center">
                                            <span class="badge bg-info text-dark rounded-pill">{{ $modulo->alumnos_count }}</span>
                                        </td>
                                        <td class="text-center">
                                            {{-- Botón VER (Ir a RAs del módulo) --}}
                                            <a href="{{ route('gestion.ras.index', ['proyecto_id' => $modulo->proyecto_id, 'modulo_id' => $modulo->id_modulo]) }}" 
                                               class="btn btn-sm btn-info text-white shadow-sm me-1" title="Ver RAs">
                                                <i class="bi bi-eye-fill"></i>RAs
                                            </a>
                                            {{-- Botón EDITAR (Ir a editar módulo) --}}
                                            <a href="{{ route('gestion.modulos.edit', ['proyecto_id' => $modulo->proyecto_id, 'modulo_id' => $modulo->id_modulo]) }}" 
                                               class="btn btn-sm btn-warning shadow-sm" title="Editar Módulo">
                                                <i class="bi bi-pencil-square"></i>Editar
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- TABLA 2: ALUMNOS TUTORIZADOS --}}
            <div class="card shadow mb-4">
                <div class="card-header py-3 bg-white">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="bi bi-people-fill me-2"></i>Tutoría Docente
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle w-100" id="tabla-alumnos">
                            <thead class="table-light">
                                <tr>
                                    <th>Proyecto</th>
                                    <th>Nombre del Alumno</th>
                                    <th class="text-center" style="width: 120px;">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($alumnosTutorizados as $alumno)
                                    <tr>
                                        <td><span class="badge bg-light text-dark border">{{ $alumno->proyecto_nombre }}</span></td>
                                        <td class="fw-bold">{{ $alumno->nombre }}</td>
                                        <td class="text-center">
                                            {{-- 
                                                NOTA: Aquí generamos el enlace a 'alumno.show'.
                                                Es probable que necesites ajustar AlumnoController@showAlumno 
                                                para que sepa buscar en la BD del proyecto, ya que el ID 
                                                puede no ser único globalmente o no encontrarse si no conectas la BD correcta.
                                            --}}
                                            <a href="{{ route('gestion.alumnos.show', ['proyecto_id' => $alumno->proyecto_id , 'alumno_id' => $alumno->id_alumno]) }}" 
                                               class="btn btn-sm btn-info shadow-sm">
                                                <i class="bi bi-person-lines-fill me-1"></i> Ver
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection