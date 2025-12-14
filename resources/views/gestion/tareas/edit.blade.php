@extends('layouts.default')

@section('title', 'Editar Tarea')

@section('scripts')
    <script>
        $(document).ready(function() {
            // Inicializar DataTables para la lista de alumnos
            $('#alumnos-tarea-datatable').DataTable({
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
                    "paginate": { "first": "First", "last": "Last", "next": "Next", "previous": "Prev" }
                },
                "pageLength": 25,
                "columnDefs": [
                    { "orderable": false, "targets": [6, 7, 8] } // No ordenar por Apto, Bloqueo, Acciones
                ]
            });

            // AJAX - COMPONENTE FECHA
            $('body').on('change', '.input-fecha-ajax', function() {
                var input = $(this);
                var url = input.data('url');
                var fecha = input.val();
                var indicator = input.siblings('.status-indicator');

                // 1. Estado Visual: Guardando (Amarillo)
                input.removeClass('border-success border-danger').addClass('border-warning');
                indicator.html('<span class="text-warning fw-bold">Guardando...</span>');

                $.ajax({
                    url: url,
                    method: 'PUT',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: { fecha: fecha },
                    success: function(response) {
                        // 2. Éxito (Verde)
                        input.removeClass('border-warning').addClass('border-success');
                        indicator.html('<span class="text-success fw-bold"><i class="bi bi-check"></i> Guardado</span>');

                        // Limpiar después de 2 segundos
                        setTimeout(function() {
                            input.removeClass('border-success');
                            indicator.fadeOut(500, function(){ $(this).html('').show(); });
                        }, 2000);
                    },
                    error: function(xhr) {
                        // 3. Error (Rojo)
                        input.removeClass('border-warning').addClass('border-danger');
                        indicator.html('<span class="text-danger fw-bold">Error</span>');
                        console.error(xhr);
                        alert('Error al guardar la fecha.');
                    }
                });
            });

            // --- AJAX - COMPONENTE DE DURACIÓN ---
            $('body').on('change', '.select-duracion-ajax', function() {
                var input = $(this);
                var url = input.data('url'); // El componente recibe la URL por prop y la pone aquí
                var duracion = input.val();
                var indicator = input.siblings('.status-indicator'); // Busca el div hermano dentro del componente

                // 1. Estado Visual: Guardando
                input.removeClass('border-success border-danger').addClass('border-warning');
                indicator.html('<span class="text-warning fw-bold">Guardando...</span>');

                $.ajax({
                    url: url,
                    method: 'PUT',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: { duracion: duracion },
                    success: function(response) {
                        // 2. Éxito
                        input.removeClass('border-warning').addClass('border-success');
                        indicator.html('<span class="text-success fw-bold"><i class="bi bi-check"></i> Guardado</span>');
                        
                        // Limpiar tras 2 segundos
                        setTimeout(function() {
                            input.removeClass('border-success');
                            indicator.fadeOut(500, function(){ $(this).html('').show(); });
                        }, 2000);
                    },
                    error: function(xhr) {
                        // 3. Error
                        input.removeClass('border-warning').addClass('border-danger');
                        indicator.html('<span class="text-danger fw-bold">Error</span>');
                    }
                });
            });

            // --- AJAX - BLOQUEO MASIVO ---
            $('.btn-accion-masiva').click(function() {
                var btn = $(this);
                var bloquear = btn.data('bloqueado'); // 1 o 0
                var url = "{{ route('gestion.tareas.toggleBloqueoMasivo', ['proyecto_id' => $proyecto_id, 'tarea_id' => $tareaPrincipal->id_tarea]) }}";
                var msgDiv = $('#msg-bloqueo-masivo');

                // Feedback visual
                btn.prop('disabled', true);
                msgDiv.html('<span class="text-warning">Procesando...</span>');

                $.ajax({
                    url: url,
                    method: 'PUT',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: { bloqueado: bloquear },
                    success: function(response) {
                        // 1. Mostrar mensaje éxito
                        msgDiv.html('<span class="text-success"><i class="bi bi-check-circle"></i> ' + (bloquear ? 'Todos bloqueados' : 'Todos desbloqueados') + '</span>');
                        
                        // 2. ACTUALIZAR VISUALMENTE LA TABLA DE ABAJO
                        // Buscamos todos los checkboxes de tipo switch en la tabla y los marcamos/desmarcamos
                        // Nota: Asumiendo que los switches de la tabla son <input type="checkbox" role="switch">
                        $('#alumnos-tarea-datatable input[role="switch"]').prop('checked', bloquear == 1);

                        // Reactivar botón
                        setTimeout(() => { 
                            btn.prop('disabled', false); 
                            msgDiv.fadeOut(2000, function() { $(this).html('').show(); });
                        }, 1000);
                    },
                    error: function(xhr) {
                        msgDiv.html('<span class="text-danger">Error al procesar la solicitud.</span>');
                        btn.prop('disabled', false);
                    }
                });
            });

            // --- AJAX - APTO/NO APTO ---
            $('body').on('change', '.check-apto-ajax', function() {
                var input = $(this);
                var url = input.data('url');
                var esApto = input.is(':checked') ? 1 : 0; // Convertimos a 1 o 0
                var indicator = input.siblings('.status-indicator'); // Buscamos el div hermano (el indicador está fuera del form-check div si usaste mi html anterior, ajusta según estructura)
                
                // Ajuste: como el input está dentro de un div .form-check, el indicador está en el td padre
                // Subimos al padre (div) y luego buscamos el hermano o subimos al td y buscamos .status-indicator
                indicator = input.closest('td').find('.status-indicator');

                // 1. Visual: Guardando
                indicator.html('<span class="text-warning fw-bold">Guardando...</span>');

                $.ajax({
                    url: url,
                    method: 'PUT',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: { apto: esApto },
                    success: function(response) {
                        // 2. Éxito
                        // Cambiamos borde visualmente si es apto o no
                        if(esApto) {
                            input.removeClass('border-secondary border-danger').addClass('border-success bg-success');
                        } else {
                            input.removeClass('border-success bg-success').addClass('border-secondary');
                        }

                        indicator.html('<span class="text-success fw-bold"><i class="bi bi-check"></i> Guardado</span>');

                        setTimeout(function() {
                            indicator.fadeOut(500, function(){ $(this).html('').show(); });
                        }, 2000);
                    },
                    error: function(xhr) {
                        // 3. Error
                        // Revertimos el checkbox porque falló
                        input.prop('checked', !esApto);
                        indicator.html('<span class="text-danger fw-bold">Error</span>');
                        alert('Error al guardar la calificación.');
                    }
                });
            });

            // --- AJAX - BLOQUEO INDIVIDUAL ---
            $('body').on('change', '.check-bloqueo-ajax', function() {
                var input = $(this);
                var url = input.data('url');
                var bloqueado = input.is(':checked') ? 1 : 0;
                
                // Buscamos el indicador subiendo al td padre
                var indicator = input.closest('td').find('.status-indicator');

                // 1. Visual: Procesando
                indicator.html('<span class="text-warning fw-bold">...</span>');

                $.ajax({
                    url: url,
                    method: 'PUT',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                    data: { bloqueado: bloqueado },
                    success: function(response) {
                        // 2. Éxito
                        // Feedback visual diferente si bloqueamos o desbloqueamos
                        if (bloqueado) {
                            indicator.html('<span class="text-danger fw-bold"><i class="bi bi-lock-fill"></i> Bloqueado</span>');
                        } else {
                            indicator.html('<span class="text-success fw-bold"><i class="bi bi-unlock-fill"></i> Abierto</span>');
                        }

                        setTimeout(function() {
                            indicator.fadeOut(500, function(){ $(this).html('').show(); });
                        }, 2000);
                    },
                    error: function(xhr) {
                        // 3. Error
                        input.prop('checked', !bloqueado); // Revertir cambio
                        indicator.html('<span class="text-danger fw-bold">Error</span>');
                    }
                });
            });
        });

        // Para seleccionar todos dentro del modal de añadir más alumnos
        document.getElementById('checkAllAlumnos')?.addEventListener('change', function() {
            var checkboxes = document.querySelectorAll('.check-alumno');
            for (var checkbox of checkboxes) {
                checkbox.checked = this.checked;
            }
        });
    </script>
@endsection

@section('content')
<div class="container-fluid">
    @if(auth()->user()->isAdmin())
        @include('gestion.layouts.header')
    @elseif(auth()->user()->isProfesor())
        @include('profesores.layouts.header')
    @endif

    {{-- CABECERA --}}
    <div class="d-flex justify-content-between align-items-center mb-4 mt-4">
        <div>
            <h2 class="mb-0 texto">Tarea: <strong class="text-info" {{ $tareaPrincipal->nombre }} </strong> <strong class="text-info">{{ $modulo->nombre }}</strong></h2>
        </div>
        <a href="javascript:history.back()" class="btn btn-danger shadow-sm">
            <i class="bi bi-arrow-left me-1"></i> Volver
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

    {{-- BLOQUE 1: DEFINICIÓN GENERAL DE LA TAREA --}}
    <div class="card shadow mb-5 border-left-primary">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold text-primary">Descripción</h6>
        </div>
        <div class="card-body">
            {{-- Formulario para actualizar la descripción (Nombre, Descripción, Criterios) --}}
            <form action="{{ route('gestion.tareas.update', ['proyecto_id' => $proyecto_id, 'tarea_id' => $tareaPrincipal->id_tarea]) }}" method="POST">
                @csrf
                @method('PUT')
                <input type="hidden" name="modo" value="definicion"> {{-- Campo oculto para distinguir qué actualizamos --}}

                <div class="row g-3">
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Actividad</label>
                        <input type="text" name="nombre" class="form-control" value="{{ $tareaPrincipal->nombre }}" placeholder="Ej: MMA1" required>
                    </div>
                    
                    <div class="col-md-2">
                        <label class="form-label fw-bold">Tarea</label>
                        <input type="text" name="tarea" class="form-control" value="{{ $tareaPrincipal->tarea }}" required>
                    </div>

                    <div class="col-md-8">
                        <label class="form-label fw-bold">Descripción / Instrucciones</label>
                        <textarea name="descripcion" class="form-control" rows="1">{{ $tareaPrincipal->descripcion }}</textarea>
                    </div>
                </div>

                <hr class="my-4">

                <h6 class="fw-bold text-secondary mb-3">Criterios de Evaluación Asociados</h6>
                <div class="accordion" id="accCriterios">
                    @foreach($modulo->ras as $ra)
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="h-{{$ra->id_ras}}">
                                <button class="accordion-button collapsed py-2 bg-light" type="button" data-bs-toggle="collapse" data-bs-target="#c-{{$ra->id_ras}}">
                                    <strong>{{ $ra->codigo }}</strong>: {{ Str::limit($ra->descripcion, 60) }}
                                </button>
                            </h2>
                            <div id="c-{{$ra->id_ras}}" class="accordion-collapse collapse" data-bs-parent="#accCriterios">
                                <div class="accordion-body p-2">
                                    <div class="row">
                                        @foreach($ra->criterios as $crit)
                                            <div class="col-md-4 col-sm-6">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="criterios[]" 
                                                           value="{{ $crit->id_criterio }}" 
                                                           id="cr-{{$crit->id_criterio}}"
                                                           {{ in_array($crit->id_criterio, $criteriosIds) ? 'checked' : '' }}>
                                                    <label class="form-check-label small" for="cr-{{$crit->id_criterio}}">
                                                        <span class="badge bg-secondary me-1">{{ $crit->ce }}</span> {{ $crit->descripcion }}
                                                    </label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-3 text-end">
                    <div class="mt-3 d-flex justify-content-end gap-4 align-items-center">
    
                        {{-- GRUPO DE ACCIONES MASIVAS DE BLOQUEO (AJAX) --}}
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-outline-danger btn-accion-masiva" data-bloqueado="1">
                                <i class="bi bi-lock-fill me-1"></i> Bloquear Todos
                            </button>
                            <button type="button" class="btn btn-outline-success btn-accion-masiva" data-bloqueado="0">
                                <i class="bi bi-unlock-fill me-1"></i> Desbloquear Todos
                            </button>
                        </div>

                        {{-- Botón Original --}}
                        <button type="submit" class="btn btn-success fw-bold">
                            <i class="bi bi-save me-1"></i> Guardar
                        </button>
                    </div>
                    {{-- Mensaje de estado para el bloqueo masivo --}}
                    <div id="msg-bloqueo-masivo" class="mt-2 small fw-bold"></div>
                </div>
            </form>
        </div>
    </div>

    {{-- BLOQUE 2: LISTADO DE ALUMNOS (DataTable) --}}
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-white">
            <h6 class="m-0 font-weight-bold text-primary">Seguimiento Individual de Alumnos</h6>
            @if(auth()->user()->isProfesor() || auth()->user()->isAdmin())
                <button class="btn btn-sm btn-success shadow-sm mb-2 mt-2" data-bs-toggle="modal" data-bs-target="#modalAsignarAlumnos">
                    <i class="bi bi-person-plus-fill me-1"></i> Asignar a más alumnos
                </button>
            @endif
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle" id="alumnos-tarea-datatable" width="100%">
                    <thead class="table-light">
                        <tr>
                            <th>Alumno</th>
                            <th>Tutor Laboral</th>
                            <th>Tutor Docente</th>
                            <th class="text-center">Fecha</th>
                            <th class="text-center">Duración</th>
                            <th>Notas Alumno</th>
                            <th class="text-center">Apto</th>
                            
                            @if(auth()->user()->isProfesor() || auth()->user()->isAdmin())
                                <th class="text-center"><i class="bi bi-lock-fill"></i>Bloqueo</th>
                            @endif
                            
                            <th class="text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($asignaciones as $item)
                            <tr>
                                {{-- 1. Alumno --}}
                                <td class="fw-bold">{{ $item->alumno->nombre }}</td>

                                {{-- 2. Tutor Laboral --}}
                                <td><small>{{ $item->alumno->tutorLaboral->nombre ?? '-' }}</small></td>

                                {{-- 3. Tutor Docente --}}
                                <td><small>{{ $item->alumno->tutorDocente->nombre ?? '-' }}</small></td>

                                {{-- 4. Fecha (AJAX en caliente) --}}
                                <td class="text-center position-relative">
                                    <input type="date" 
                                        class="form-control form-control-sm text-center input-fecha-ajax" 
                                        style="min-width: 130px;"
                                        value="{{ $item->fecha ? \Carbon\Carbon::parse($item->fecha)->format('Y-m-d') : '' }}"
                                        
                                        {{-- Generamos la ruta aquí para que JS solo tenga que leerla --}}
                                        data-url="{{ route('gestion.tareas.updateFecha', ['proyecto_id' => $proyecto_id, 'tarea_id' => $item->id_tarea]) }}"
                                        
                                        {{-- Lógica de bloqueo --}}
                                        {{ ($item->bloqueado && !auth()->user()->isProfesor() && !auth()->user()->isAdmin()) ? 'disabled' : '' }}>
                                        
                                    {{-- Pequeño indicador visual de guardado --}}
                                    <div class="status-indicator" style="font-size: 0.7rem; height: 15px; position: absolute; width: 100%; left: 0;"></div>
                                </td>

                                {{-- 5. Duración (Componente Reutilizable) --}}
                                <td class="text-center">
                                    <x-duration-select 
                                        :selected="$item->duracion"
                                        :url="route('gestion.tareas.updateDuracion', ['proyecto_id' => $proyecto_id, 'tarea_id' => $item->id_tarea])"
                                        :disabled="$item->bloqueado && !auth()->user()->isProfesor() && !auth()->user()->isAdmin()"
                                    />
                                </td>

                                {{-- 6. Notas --}}
                                <td><small class="text-muted fst-italic">{{ Str::limit($item->notas_alumno, 50) ?? '-' }}</small></td>

                                {{-- 7. APTO (Checkbox AJAX) --}}
                                <td class="text-center position-relative">
                                    <div class="form-check d-flex justify-content-center">
                                        <input class="form-check-input border-2 border-secondary check-apto-ajax" 
                                            type="checkbox" 
                                            style="cursor: pointer; transform: scale(1.2);"
                                            
                                            {{-- Generamos ruta --}}
                                            data-url="{{ route('gestion.tareas.updateApto', ['proyecto_id' => $proyecto_id, 'tarea_id' => $item->id_tarea]) }}"
                                            
                                            {{-- Estado actual --}}
                                            {{ $item->apto ? 'checked' : '' }}
                                            
                                            {{-- Bloqueo --}}
                                            {{ ($item->bloqueado && !auth()->user()->isProfesor() && !auth()->user()->isAdmin()) ? 'disabled' : '' }}>
                                    </div>
                                    
                                    {{-- Indicador visual --}}
                                    <div class="status-indicator" style="font-size: 0.7rem; height: 15px; position: absolute; width: 100%; left: 0;"></div>
                                </td>

                                {{-- 8. BLOQUEO (Switch AJAX) --}}
                                @if(auth()->user()->isProfesor() || auth()->user()->isAdmin())
                                    <td class="text-center position-relative">
                                        <div class="form-check form-switch d-flex justify-content-center">
                                            <input class="form-check-input check-bloqueo-ajax" 
                                                type="checkbox" 
                                                role="switch" 
                                                style="cursor: pointer;"
                                                
                                                {{-- Generamos ruta --}}
                                                data-url="{{ route('gestion.tareas.updateBloqueo', ['proyecto_id' => $proyecto_id, 'tarea_id' => $item->id_tarea]) }}"
                                                
                                                {{-- Estado actual --}}
                                                {{ $item->bloqueado ? 'checked' : '' }}>
                                        </div>
                                        
                                        {{-- Indicador visual --}}
                                        <div class="status-indicator" style="font-size: 0.7rem; height: 15px; position: absolute; width: 100%; left: 0;"></div>
                                    </td>
                                @endif

                                {{-- 9. ELIMINAR (Solo esta asignación) --}}
                                <td class="text-center">
                                    @if(auth()->user()->isProfesor() || auth()->user()->isAdmin())
                                        <form action="{{ route('gestion.tareas.destroy', ['proyecto_id' => $proyecto_id, 'tarea_id' => $item->id_tarea]) }}" 
                                              method="POST" 
                                              onsubmit="return confirm('¿Quitar esta tarea al alumno {{ $item->alumno->nombre }}?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger shadow-sm">
                                                <i class="bi bi-trash-fill"></i>Eliminar
                                            </button>
                                        </form>
                                    @endif
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