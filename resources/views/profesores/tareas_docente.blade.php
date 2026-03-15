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
                "zeroRecords": "No hay coincidencias",
                "paginate": {
                    "first": "Primero",
                    "last": "Último",
                    "next": "Siguiente",
                    "previous": "Anterior"
                }
            },
            order: [
                [4, "desc"]
            ],
            responsive: true,
            autoWidth: false,
            columnDefs: [{
                    orderable: false,
                    targets: [1, 4, 5, 6, 7]
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
                    targets: [0, 1]
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
            var url = input.data('url');
            var fecha = input.val();
            var indicator = input.siblings('.status-indicator');

            // 1. Estado Visual: Guardando (Amarillo)
            input.removeClass('border-success border-danger').addClass('border-warning');
            indicator.html('<span class="text-warning fw-bold">Guardando...</span>');

            $.ajax({
                url: url,
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: {
                    fecha: fecha
                },
                success: function(response) {
                    // 2. Éxito (Verde)
                    input.removeClass('border-warning').addClass('border-success');
                    indicator.html('<span class="text-success fw-bold"><i class="bi bi-check"></i> Guardado</span>');

                    // Limpiar después de 2 segundos
                    setTimeout(function() {
                        input.removeClass('border-success');
                        indicator.fadeOut(500, function() {
                            $(this).html('').show();
                        });
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
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: {
                    duracion: duracion
                },
                success: function(response) {
                    // 2. Éxito
                    input.removeClass('border-warning').addClass('border-success');
                    indicator.html('<span class="text-success fw-bold"><i class="bi bi-check"></i> Guardado</span>');

                    // Limpiar tras 2 segundos
                    setTimeout(function() {
                        input.removeClass('border-success');
                        indicator.fadeOut(500, function() {
                            $(this).html('').show();
                        });
                    }, 2000);
                },
                error: function(xhr) {
                    // 3. Error
                    input.removeClass('border-warning').addClass('border-danger');
                    indicator.html('<span class="text-danger fw-bold">Error</span>');
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
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: {
                    apto: esApto
                },
                success: function(response) {
                    // 2. Éxito
                    // Cambiamos borde visualmente si es apto o no
                    if (esApto) {
                        input.removeClass('border-secondary border-danger').addClass('border-success bg-success');
                    } else {
                        input.removeClass('border-success bg-success').addClass('border-secondary');
                    }

                    indicator.html('<span class="text-success fw-bold"><i class="bi bi-check"></i> Guardado</span>');

                    setTimeout(function() {
                        indicator.fadeOut(500, function() {
                            $(this).html('').show();
                        });
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

        // --- AJAX - BLOQUEO INDIVIDUAL ---
        $('body').on('change', '.check-bloqueo-ajax', function() {
            var input = $(this);
            var url = input.data('url');
            var bloqueado = input.is(':checked') ? 1 : 0;

            // Localizamos la fila actual para encontrar el input de calificación
            var row = input.closest('tr');
            var inputCalificacion = row.find('.input-calificacion-ajax');
            var indicator = input.closest('td').find('.status-indicator');

            // 1. Visual: Procesando
            indicator.html('<span class="text-warning fw-bold">...</span>');

            $.ajax({
                url: url,
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: {
                    bloqueado: bloqueado
                },
                success: function(response) {
                    if (bloqueado) {
                        inputCalificacion.prop('disabled', true); // Bloquea el input
                        indicator.html('<span class="text-danger fw-bold"><i class="bi bi-lock-fill"></i> Bloqueado</span>');
                    } else {
                        inputCalificacion.prop('disabled', false); // Desbloquea el input
                        indicator.html('<span class="text-success fw-bold"><i class="bi bi-unlock-fill"></i> Abierto</span>');
                    }

                    setTimeout(function() {
                        indicator.fadeOut(500, function() {
                            $(this).html('').show();
                        });
                    }, 2000);
                },
                error: function(xhr) {
                    // Revertir cambio en el switch si falla
                    input.prop('checked', !bloqueado);
                    indicator.html('<span class="text-danger fw-bold">Error</span>');
                }
            });
        });

        // --- AJAX - GUARDAR TEXTOS ---
        $('body').on('click', '.btn-guardar-fila', function(e) {
            e.preventDefault();
            var btn = $(this);
            var row = btn.closest('tr');
            var url = btn.data('url');

            // Recopilamos los datos de los inputs de la fila
            var data = {
                nombre: row.find('input[name="nombre"]').val(),
                tarea: row.find('input[name="tarea"]').val(),
                descripcion: row.find('textarea[name="descripcion"]').val(),
                notas_alumno: row.find('input[name="notas_alumno"]').val(),
                modo: 'definicion' // Para que el controlador sepa qué hacer
            };

            var originalContent = btn.html();
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Guardando...');

            $.ajax({
                url: url,
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: data,
                success: function(response) {
                    // Éxito
                    btn.removeClass('btn-success').addClass('btn-dark')
                        .html('<i class="bi bi-check-lg"></i> Guardado');

                    // Volver al estado original tras 2 seg
                    setTimeout(function() {
                        btn.prop('disabled', false).removeClass('btn-dark').addClass('btn-success').html(originalContent);
                    }, 2000);
                },
                error: function(xhr) {
                    btn.prop('disabled', false).html('Error');
                    alert('Error al guardar los datos de texto.');
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

    {{-- Encabezado con botón volver --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold texto">Historial de Tareas</h2>
            <h5 class="texto">Alumno: <span class="text-warning">{{ $alumno->nombre }}</span></h5>
            <p class="small texto mb-0">Proyecto: <span class="text-warning">{{ $proyecto->proyecto }}</span></p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="javascript:history.back()" class="btn btn-danger d-flex align-items-center me-2 ms-2">
                <i class="bi bi-arrow-left-circle"></i> Volver
            </a>
        </div>
    </div>

    {{-- Alertas --}}
    @if ($errors->any())
    <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="mb-0 fw-bold text-primary"><i class="bi bi-journal-text me-2"></i>Tareas Realizadas</h6>
        </div>

        {{-- p-0 en el body para ganar espacio en móvil --}}
        <div class="card-body p-0 p-md-3">
            <div class="table-responsive">
                <table id="tablaTareas" class="table table-hover align-middle w-100">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th class="text-start">Coment. alumno</th>
                            <th>Tarea</th>
                            <th class="text-start">Descripción</th> {{-- Oculto en móvil --}}
                            <th class="text-center">Fecha</th>
                            <th>Duración</th>
                            <th class="text-center">Calif.</th>
                            <th class="text-center">Bloq.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($tareas as $tarea)
                        <tr>
                            {{-- 0. Nombre (Actividad) --}}
                            <td>
                                <div style="max-width: 120px;">
                                    <p><span class="text-primary fw-bold">{{ $tarea->actividadNombre }}</span></p>
                                </div>
                            </td>

                            {{-- 1. Notas del alumno --}}
                            <td>
                                <input name="notas_alumno" class="form-control form-control-sm text-muted"
                                    value="{{ $tarea->notas_alumno }}">
                            </td>

                            {{-- 2. Tarea --}}
                            <td>
                                <div>
                                    <p>{{ $tarea->actividadTarea }}</p>
                                </div>
                            </td>

                            {{-- 3. Descripción --}}
                            <td>
                                <p>{{ $tarea->actividadDescripcion }}</p>
                            </td>

                            {{-- 4. Fecha --}}
                            <td class="text-center position-relative">
                                <div style="min-width: 140px;">
                                    <input type="date"
                                        class="form-control form-control-sm text-center input-fecha-ajax"
                                        value="{{ $tarea->fecha ? \Carbon\Carbon::parse($tarea->fecha)->format('Y-m-d') : '' }}"
                                        data-url="{{ route('gestion.tareas.updateFecha', ['proyecto_id' => $proyecto->id_base_de_datos, 'tarea_id' => $tarea->id_tarea]) }}">

                                    <div class="status-indicator small position-absolute w-100 start-0" style="bottom: -18px;"></div>
                                </div>
                            </td>

                            {{-- 5. Duración --}}
                            <td class="text-center">
                                <div style="min-width: 100px;">
                                    <x-duration-select
                                        class="form-select-sm"
                                        :selected="$tarea->duracion ? \Carbon\Carbon::parse($tarea->duracion)->format('H:i') : ''"
                                        :url="route('gestion.tareas.updateDuracion', ['proyecto_id' => $proyecto->id_base_de_datos, 'tarea_id' => $tarea->id_tarea])"
                                        :disabled="false" />
                                    <div class="status-indicator small position-absolute w-100 start-0" style="bottom: -5px;"></div>
                                </div>
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

                            {{-- 7. Bloqueo --}}
                            <td class="text-center position-relative">
                                <div class="form-check form-switch d-flex justify-content-center">
                                    <input class="form-check-input check-bloqueo-ajax"
                                        type="checkbox"
                                        role="switch"
                                        style="cursor: pointer;"
                                        data-url="{{ route('gestion.tareas.updateBloqueo', ['proyecto_id' => $proyecto->id_base_de_datos, 'tarea_id' => $tarea->id_tarea]) }}"
                                        {{ $tarea->bloqueado ? 'checked' : '' }}>
                                </div>
                                <div class="status-indicator small position-absolute w-100 start-0" style="bottom: -5px;"></div>
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