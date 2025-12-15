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
                    "first": "First",
                    "last": "Last",
                    "next": "Next",
                    "previous": "Prev"
                }
            },
            order: [
                [1, "desc"]
            ],
            responsive: true,
            autoWidth: false,
            columnDefs: [{
                    orderable: false,
                    targets: [4, 5, 6]
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
                    targets: [0, 6]
                },
                {
                    responsivePriority: 2,
                    targets: [2, 5]
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
                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
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
@if(auth()->user()->isTutorLaboral())
    @include('tutores_laborales.layouts.header')
@endif

<div class="container-fluid py-4">

    {{-- Encabezado con botón volver --}}
    <div class="d-flex justify-content-start align-items-center m-4">
        <div>
            <h2 class="fw-bold texto">Historial de Tareas</h2>
            <h5 class="texto">Alumno: <span class="text-warning">{{ $alumno->nombre }}</span></h5>
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
                            <th class="text-start">Coment. alumno</th>
                            <th>Tarea</th>
                            <th class="text-start">Descripción</th>
                            <th class="text-center">Fecha</th>
                            <th>Duración</th>
                            <th class="text-center">Calif.</th>
                            <th class="text-center">Bloq.</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tareas as $tarea)
                        <tr>
                            {{-- 0. Notas del alumno --}}
                            <td>
                                <p>{{ $tarea->notas_alumno }}"</p>
                            </td>

                            {{-- 1. Tarea --}}
                            <td>
                                <div>
                                    <p>{{ $tarea->actividadTarea }}</p>
                                </div>
                            </td>

                            {{-- 2. Descripción --}}
                            <td>
                                <p>{{ $tarea->actividadDescripcion }}</p>
                            </td>

                            {{-- 3. Fecha --}}
                            <td class="text-center position-relative">
                                <div style="min-width: 140px;">
                                    <input type="date"
                                        class="form-control form-control-sm text-center input-fecha-ajax"
                                        value="{{ $tarea->fecha ? \Carbon\Carbon::parse($tarea->fecha)->format('Y-m-d') : '' }}"
                                        data-url="{{ route('gestion.tareas.updateFecha', ['proyecto_id' => $proyecto->id_base_de_datos, 'tarea_id' => $tarea->id_tarea]) }}"
                                        {{ $tarea->bloqueado ? 'disabled' : ''}}>
                                    
                                    <div class="status-indicator small position-absolute w-100 start-0" style="bottom: -18px;"></div>
                                </div>
                            </td>

                            {{-- 4. Duración --}}
                            <td class="text-center">
                                <div style="min-width: 100px;">
                                    <x-duration-select
                                        class="form-select-sm"
                                        :selected="$tarea->duracion ? \Carbon\Carbon::parse($tarea->duracion)->format('H:i') : ''"
                                        :url="route('gestion.tareas.updateDuracion', ['proyecto_id' => $proyecto->id_base_de_datos, 'tarea_id' => $tarea->id_tarea])"
                                        :disabled="$tarea->bloqueado" />
                                     <div class="status-indicator small position-absolute w-100 start-0" style="bottom: -5px;"></div>
                                </div>
                            </td>

                            {{-- 5. Calificación --}}
                            <td class="text-center position-relative">
                                <div class="form-check d-flex justify-content-center">
                                    <input class="form-check-input check-apto-ajax border-2" 
                                           type="checkbox" 
                                           style="cursor: pointer; transform: scale(1.3);"
                                           data-url="{{ route('gestion.tareas.updateApto', ['proyecto_id' => $proyecto->id_base_de_datos, 'tarea_id' => $tarea->id_tarea]) }}"
                                           {{ $tarea->apto ? 'checked' : '' }}
                                           {{ $tarea->bloqueado ? 'disabled' : ''}}>
                                </div>
                                <div class="status-indicator small position-absolute w-100 start-0" style="bottom: -5px;"></div>
                            </td>

                            {{-- 6. Bloqueo --}}
                            <td class="text-center position-relative">
                                <div class="form-check form-switch d-flex justify-content-center">
                                    <input class="form-check-input check-bloqueo-ajax"
                                        type="checkbox"
                                        role="switch"
                                        style="cursor: pointer;"
                                        disabled
                                        data-url="{{ route('gestion.tareas.updateBloqueo', ['proyecto_id' => $proyecto->id_base_de_datos, 'tarea_id' => $tarea->id_tarea]) }}"
                                        {{ $tarea->bloqueado ? 'checked' : '' }}>
                                </div>
                                <div class="status-indicator small position-absolute w-100 start-0" style="bottom: -5px;"></div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                No hay tareas registradas.
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