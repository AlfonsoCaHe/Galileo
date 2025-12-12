@extends('layouts.default')

@push('scripts')
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
            ] // Ordenar por fecha descendente por defecto
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
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                data: {
                    bloqueado: bloqueado
                },
                success: function(response) {
                    // 2. Éxito
                    // Feedback visual diferente si bloqueamos o desbloqueamos
                    if (bloqueado) {
                        indicator.html('<span class="text-danger fw-bold"><i class="bi bi-lock-fill"></i> Bloqueado</span>');
                    } else {
                        indicator.html('<span class="text-success fw-bold"><i class="bi bi-unlock-fill"></i> Abierto</span>');
                    }

                    setTimeout(function() {
                        indicator.fadeOut(500, function() {
                            $(this).html('').show();
                        });
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
</script>
@endpush

@section('content')
@if(auth()->user()->isProfesor())
    @include('profesores.layouts.header')
@endif
<div class="container-fluid py-4">

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
        <div class="card-header bg-white py-3">
            <h6 class="mb-0 fw-bold text-primary"><i class="bi bi-journal-text me-2"></i>Tareas Entregadas</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="tablaTareas" class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Nombre</th>
                            <th>Tarea</th>
                            <th>Descripción / Título</th>
                            <th>Anotaciones del alumno</th>
                            <th class="text-center">Fecha Entrega</th>
                            <th>Duración</th>
                            <th class="text-center">Calificación</th>
                            <th class="text-end">Bloquear</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tareas as $tarea)
                        <form action="{{ route('gestion.tareas.update', ['proyecto_id' => $proyecto->id_base_de_datos, 'tarea_id' => $tarea->id_tarea]) }}" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="modo" value="definicion"> {{-- Campo oculto para distinguir qué actualizamos --}}
                            <tr>
                                {{-- Nombre --}}
                                <td>
                                    <label class="form-label fw-bold">Actividad</label>
                                    <input type="text" name="nombre" class="form-control" value="{{ $tarea->nombre }}" placeholder="Ej: MMA1" required>
                                </td>

                                {{-- Tarea --}}
                                <td>
                                    <label class="form-label fw-bold">Tarea</label>
                                    <input type="text" name="tarea" class="form-control" value="{{ $tarea->tarea }}"placeholder="Texto que aparecerá en el desplegable del alumno" required>
                                </td>

                                {{-- Descripción --}}
                                <td>
                                    <label class="form-label fw-bold">Descripción / Instrucciones</label>
                                    <textarea name="descripcion" class="form-control" rows="1">{{ $tarea->descripcion }}</textarea>
                                </td>

                                {{-- Notas del alumno --}}
                                <td>
                                    <input name="notas_alumno" class="form-control" value="{{ $tarea->notas_alumno ?? 'Sin descripción' }}"></input>
                                </td>

                                {{-- Fecha de creación --}}
                                <td class="text-center position-relative">
                                    <input type="date" 
                                        class="form-control form-control-sm text-center input-fecha-ajax" 
                                        style="min-width: 130px;"
                                        value="{{ $tarea->fecha ? \Carbon\Carbon::parse($tarea->fecha)->format('Y-m-d') : '' }}"
                                        
                                        {{-- Generamos la ruta aquí para que JS solo tenga que leerla --}}
                                        data-url="{{ route('gestion.tareas.updateFecha', ['proyecto_id' => $proyecto->id_base_de_datos, 'tarea_id' => $tarea->id_tarea]) }}"
                                        
                                    {{-- Pequeño indicador visual de guardado --}}
                                    <div class="status-indicator" style="font-size: 0.7rem; height: 15px; position: absolute; width: 100%; left: 0;"></div>
                                </td>

                                {{-- Duración --}}
                                <td class="text-center">
                                    <x-duration-select
                                        :selected="$tarea->duracion"
                                        :url="route('gestion.tareas.updateDuracion', ['proyecto_id' => $proyecto->id_base_de_datos, 'tarea_id' => $tarea->id_tarea])"
                                        :disabled="false" />
                                </td>

                                {{-- Calificación --}}
                                <td class="text-center position-relative">
                                    <div class="form-check d-flex justify-content-center">
                                        <input class="form-check-input border-2 border-secondary" type="checkbox" style="cursor: pointer; transform: scale(1.2);"
                                            {{-- Estado actual --}}
                                            {{ $tarea->apto ? 'checked' : '' }}>
                                    </div>
                                </td>

                                {{-- Bloqueo (Switch AJAX) --}}
                                <td class="text-center position-relative">
                                    <div class="form-check form-switch d-flex justify-content-center">
                                        <input class="form-check-input check-bloqueo-ajax"
                                            type="checkbox"
                                            role="switch"
                                            style="cursor: pointer;"

                                            {{-- Generamos ruta --}}
                                            data-url="{{ route('gestion.tareas.updateBloqueo', ['proyecto_id' => $proyecto->id_base_de_datos, 'tarea_id' => $tarea->id_tarea]) }}"

                                            {{-- Estado actual --}}
                                            {{ $tarea->bloqueado ? 'checked' : '' }}>
                                    </div>

                                    {{-- Indicador visual --}}
                                    <div class="status-indicator" style="font-size: 0.7rem; height: 15px; position: absolute; width: 100%; left: 0;"></div>
                                </td>

                                {{-- Acciones --}}
                                <td class="text-center">
                                    <button type="submit" class="btn btn-success fw-bold">
                                        <i class="bi bi-save me-1"></i> Guardar
                                    </button>
                                </td>
                            </tr>
                        </form>
                        @empty
                        <tr>
                            <td colspan="4" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center text-muted">
                                    <i class="bi bi-inbox fs-1 mb-2"></i>
                                    <p class="mb-0">Este alumno no tiene tareas registradas en este módulo.</p>
                                </div>
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