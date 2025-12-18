@props(['yearStart' => now()->year])

<button type="button" class="btn btn-success fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#crearProyectoModal">
    <i class="fas fa-plus-circle me-2"></i> Nuevo Proyecto
</button>

<div class="modal fade" id="crearProyectoModal" tabindex="-1" aria-labelledby="crearProyectoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg rounded-4">

            <div class="modal-header bg-primary text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold">Crear Nuevo Proyecto</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            {{-- UN SOLO FORMULARIO para todo --}}
            <form method="POST" action="{{ route('gestion.proyectos.store') }}" id="formCrearProyecto" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="proyecto_action" value="crear">

                <div class="modal-body p-4">
                    {{-- 1. Datos del Proyecto --}}
                    <div class="mb-4">
                        <label for="year_start" class="form-label fw-semibold">Año de Inicio:</label>
                        <input type="number" id="year_start" name="year_start" 
                               value="{{ old('year_start', $yearStart) }}"
                               class="form-control @error('year_start') is-invalid @enderror" required>
                        @error('year_start')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <hr>

                    {{-- 2. Excel de Alumnos (Opcional) --}}
                    <div class="mb-3">
                        <label for="archivo_excel" class="form-label fw-semibold">Importar Alumnos y Módulos (Opcional)</label>
                        <p class="text-muted small">Selecciona el CSV/Excel de Séneca para matricular alumnos automáticamente.</p>
                        <p class="small text-danger fw-bold">¡Importante! Debe contener el nombre del alumno, la unidad, los módulos matriculados y el correo electrónico.</p>
                        <input type="file" name="archivo_excel" id="archivo_excel" class="form-control">
                    </div>
                </div>

                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-success fw-bold rounded-3 shadow-sm"
                        onclick="this.disabled=true; this.innerText='Creando...'; this.form.submit();">
                        Crear e Importar
                    </button>
                    <button type="button" class="btn btn-danger fw-bold rounded-3 shadow-sm" data-bs-dismiss="modal">Cancelar</button>
                </div>
            </form>

        </div>
    </div>
</div>