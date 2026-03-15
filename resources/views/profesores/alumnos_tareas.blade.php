@extends('layouts.default')

@section('scripts')
<script>
    $(document).ready(function() {
        $('#tablaTareas').DataTable({
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
                "zeroRecords": "Este alumno no tiene tareas registradas en este módulo.",
                "paginate": {
                    "first": "First",
                    "last": "Last",
                    "next": "Next",
                    "previous": "Prev"
                }
            },
            "pageLength": 25,
            responsive: true,
            autoWidth: false,
            columnDefs: [{
                    orderable: false,
                    targets: [4, 5, 6, 7]
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
                    targets: [3, 6, 7]
                }
            ]
        });

        // AJAX - COMPONENTE FECHA
        $('body').on('change', '.input-fecha-ajax', function() {
            var input = $(this);
            ajaxRequest(input, input.val(), 'fecha');
        });

        // AJAX - COMPONENTE DE DURACIÓN
        $('body').on('change', '.select-duracion-ajax', function() {
            var input = $(this);
            ajaxRequest(input, input.val(), 'duracion');
        });

        // AJAX - APTO/NO APTO
        $('body').on('change', '.check-apto-ajax', function() {
            var input = $(this);
            var value = input.is(':checked') ? 1 : 0;
            ajaxRequest(input, value, 'apto');
        });

        // AJAX - BLOQUEO
        $('body').on('change', '.check-bloqueo-ajax', function() {
            var input = $(this);
            var isChecked = input.is(':checked');
            var value = isChecked ? 1 : 0;
            var fila = input.closest('tr');
            var inputCalificacion = fila.find('.input-calificacion-ajax');

            // Deshabilitar temporalmente el input de calificación mientras se guarda
            inputCalificacion.prop('disabled', true);

            ajaxRequest(input, value, 'bloqueado');

            // Si el resultado es exitoso (dentro de ajaxRequest podrías añadir un callback, 
            // pero para simplificar lo manejamos aquí asumiendo éxito o revirtiendo en error)
            if (!isChecked) {
                inputCalificacion.prop('disabled', false);
            }
        });

        // NUEVO: AJAX - NOTAS ALUMNO
        $('body').on('change', '.input-notas-ajax', function() {
            var input = $(this);
            ajaxRequest(input, input.val(), 'notas_alumno');
        });

        // AJAX - Cambiar Calificación
        $('body').on('change', '.input-calificacion-ajax', function() {
            var input = $(this);
            var url = input.data('url');
            var valor = input.val();
            var indicator = input.siblings('.status-indicator');

            // --- A) VALIDACIÓN FRONTEND ---
            // Si está vacío lo permitimos (quizás quiera borrar la nota), pero si tiene valor, debe ser 0-10
            if (valor !== '' && (valor < 0 || valor > 10)) {
                alert('La calificación debe ser un número entre 0 y 10.');
                input.val(''); // Limpiamos o restauramos
                input.addClass('border-danger');
                return;
            }

            // --- B) ESTADO GUARDANDO ---
            input.removeClass('border-success border-danger border-secondary').addClass('border-warning');
            indicator.html('<span class="text-warning fw-bold">Guardando...</span>');

            $.ajax({
                url: url,
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: {
                    calificacion: valor
                },
                success: function(response) {
                    // --- C) LÓGICA DE ÉXITO Y COLORES ---
                    // Convertimos a float para comparar
                    var notaNumerica = parseFloat(valor);

                    // Limpiamos colores previos
                    input.removeClass('border-warning border-danger border-success');

                    if (valor === "") {
                        // Si borró la nota, borde neutro
                        input.addClass('border-secondary');
                    } else if (notaNumerica >= 5) {
                        // APROBADO: Borde VERDE
                        input.addClass('border-success').css('border-width', '2px');
                    } else {
                        // SUSPENSO: Borde ROJO (pero con check de guardado)
                        input.addClass('border-danger').css('border-width', '2px');
                    }

                    indicator.html('<span class="text-success fw-bold"><i class="bi bi-check"></i> Guardado</span>');

                    // --- D) LIMPIEZA VISUAL TRAS 2 SEGUNDOS ---
                    setTimeout(function() {
                        // Quitamos el grosor extra del borde pero mantenemos el color semántico
                        // para que el profesor vea rápido cuáles están rojas/verdes
                        input.css('border-width', '1px');

                        // Ocultamos el texto "Guardado"
                        indicator.fadeOut(500, function() {
                            $(this).html('').show();
                        });
                    }, 2000);
                },
                error: function(xhr) {
                    // --- E) ERROR ---
                    input.removeClass('border-warning border-success').addClass('border-danger');
                    indicator.html('<span class="text-danger fw-bold">Error</span>');

                    var msg = 'Error al guardar.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    }
                    alert(msg);
                }
            });
        });

        // Función reutilizable para evitar repetir código
        function ajaxRequest(input, value, fieldName) {
            var url = input.data('url');
            // Buscamos el indicador. Si está dentro de un td, lo buscamos en el td más cercano
            var indicator = input.closest('td').find('.status-indicator');

            // Si no lo encuentra (caso input hermano), busca siblings
            if (indicator.length === 0) indicator = input.siblings('.status-indicator');

            // Feedback visual
            input.removeClass('border-success border-danger').addClass('border-warning');
            indicator.html('<span class="text-warning fw-bold">...</span>');

            var data = {};
            data[fieldName] = value;

            $.ajax({
                url: url,
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: data,
                success: function(response) {
                    input.removeClass('border-warning').addClass('border-success');

                    // Solo para checkboxes
                    if (fieldName === 'apto') {
                        value == 1 ? input.addClass('bg-success') : input.removeClass('bg-success');
                    }

                    indicator.html('<span class="text-success fw-bold"><i class="bi bi-check"></i></span>');
                    setTimeout(function() {
                        input.removeClass('border-success');
                        indicator.fadeOut(500, function() {
                            $(this).html('').show();
                        });
                    }, 2000);
                },
                error: function(xhr) {
                    input.removeClass('border-warning').addClass('border-danger');
                    if (input.attr('type') === 'checkbox') input.prop('checked', !value); // Revertir checkbox si hay un error
                    indicator.html('<span class="text-danger fw-bold">Error</span>');
                    console.error(xhr);
                }
            });
        }
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
            <h2 class="fw-bold texto">Historial de Tareas</h2>
            <h5 class="texto">Alumno: <span class="text-warning">{{ $alumno->nombre }}</span></h5>
            <p class="small texto mb-0">Módulo: <span class="text-warning">{{ $modulo->nombre }}</span> | Proyecto: <span class="text-warning">{{ $proyecto->proyecto }}</span></p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="javascript:history.back()" class="btn btn-danger d-flex align-items-center gap-2">
                <i class="bi bi-arrow-left-circle"></i> Volver
            </a>
        </div>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold text-primary"><i class="bi bi-journal-text me-2"></i>Tareas Entregadas</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tablaTareas" class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Actividad</th>
                            <th>Tarea</th>
                            <th>Descripción / Título</th>
                            <th>Anotaciones del alumno</th>
                            <th class="text-center">Fecha</th>
                            <th>Duración</th>
                            <th class="text-center">Calificación</th>
                            <th class="text-center"><i class="bi bi-lock-fill"></i>Bloqueo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tareas as $tarea)
                        <tr>
                            {{-- 0. Actividad --}}
                            <td>
                                <span class="fw-bold text-primary">{{ $tarea->actividad->nombre }}</span>
                            </td>

                            {{-- 1. Nombre--}}
                            <td>
                                <span class="fw-bold text-primary">{{ $tarea->tarea }}</span>
                            </td>

                            {{-- 2. Descripción --}}
                            <td>
                                <span class="text-muted small">{{ Str::limit($tarea->actividad->descripcion ?? 'Sin descripción', 50) }}</span>
                            </td>

                            {{-- 3. Notas del alumno --}}
                            <td class="position-relative">
                                <input type="text"
                                    class="form-control form-control-sm text-muted input-notas-ajax"
                                    value="{{ $tarea->notas_alumno }}"
                                    data-url="{{ route('gestion.tareas.updateNotas', ['proyecto_id' => $proyecto->id_base_de_datos, 'tarea_id' => $tarea->id_tarea]) }}"
                                    placeholder="Descripción del alumno...">
                                <div class="status-indicator" style="font-size: 0.7rem; position: absolute; bottom: -5px; right: 10px;"></div>
                            </td>

                            {{-- 4. Fecha --}}
                            <td class="text-center position-relative">
                                <input type="date"
                                    class="form-control form-control-sm text-center input-fecha-ajax"
                                    style="min-width: 130px;"
                                    value="{{ $tarea->fecha ? \Carbon\Carbon::parse($tarea->fecha)->format('Y-m-d') : '' }}"
                                    data-url="{{ route('gestion.tareas.updateFecha', ['proyecto_id' => $proyecto->id_base_de_datos, 'tarea_id' => $tarea->id_tarea]) }}">
                                <div class="status-indicator" style="font-size: 0.7rem; height: 15px; position: absolute; width: 100%; left: 0;"></div>
                            </td>

                            {{-- 5. Duración --}}
                            <td class="text-center position-relative">
                                <x-duration-select
                                    class="form-select-sm"
                                    :selected="old('duracion', isset($tarea) ? \Carbon\Carbon::parse($tarea->duracion)->format('H:i') : '')"
                                    :url="route('gestion.tareas.updateDuracion', ['proyecto_id' => $proyecto->id_base_de_datos, 'tarea_id' => $tarea->id_tarea])" />
                            </td>

                            {{-- 6. Calificación (Numérica 0-10) --}}
                            <td class="text-center position-relative">
                                <div style="max-width: 100px; margin: 0 auto;">
                                    <input type="number"
                                        class="form-control form-control-sm text-center input-calificacion-ajax"
                                        min="0"
                                        max="10"
                                        step="1"
                                        placeholder="-"
                                        value="{{ $tarea->calificacion ?? '' }}"
                                        data-url="{{ route('gestion.tareas.updateCalificacion', ['proyecto_id' => $proyecto->id_base_de_datos, 'tarea_id' => $tarea->id_tarea]) }}"
                                        {{ $tarea->bloqueado ? 'disabled' : '' }}>

                                    {{-- Indicador de estado (guardando/error) --}}
                                    <div class="status-indicator small position-absolute w-100 start-0" style="bottom: -18px;"></div>
                                </div>
                            </td>

                            {{-- 7. BLOQUEO --}}
                            <td class="text-center position-relative">
                                @if(auth()->user()->isProfesor() || auth()->user()->isAdmin())
                                <div class="form-check form-switch d-flex justify-content-center">
                                    <input class="form-check-input check-bloqueo-ajax"
                                        type="checkbox"
                                        role="switch"
                                        style="cursor: pointer;"
                                        data-url="{{ route('gestion.tareas.updateBloqueo', ['proyecto_id' => $proyecto->id_base_de_datos, 'tarea_id' => $tarea->id_tarea]) }}"
                                        {{ $tarea->bloqueado ? 'checked' : '' }}>
                                </div>
                                <div class="status-indicator" style="font-size: 0.7rem; height: 15px;"></div>
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