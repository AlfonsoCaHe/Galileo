@props(['yearStart' => now()->year])

<button type="button" class="btn btn-success fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#crearProyectoModal">
    <i class="fas fa-plus-circle me-2"></i> Nuevo Proyecto
</button>

<div class="modal fade" id="crearProyectoModal" tabindex="-1" aria-labelledby="crearProyectoModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content shadow-lg rounded-4">
            
            <div class="modal-header bg-primary text-white border-0 rounded-top-4">
                <h5 class="modal-title fw-bold" id="crearProyectoModalLabel">
                    Crear Nuevo Proyecto
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <div class="modal-body p-4">
                {{-- Contenedor de Errores de Laravel si falló la validación --}}
                @if ($errors->has('year_start') && old('proyecto_action') == 'crear')
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <strong>Error de Creación:</strong> El año de inicio es obligatorio.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <form method="POST" action="{{ route('gestion.proyectos.store') }}" id="formCrearProyecto">
                    @csrf
                    
                    {{-- Campo oculto para identificar qué formulario falló si hay varios modales --}}
                    <input type="hidden" name="proyecto_action" value="crear">

                    <div class="mb-3">
                        <label for="year_start" class="form-label fw-semibold">Año de Inicio del Proyecto:</label>
                        <input type="number" 
                               id="year_start" 
                               name="year_start" 
                               value="{{ old('year_start', $yearStart) }}"
                               class="form-control @error('year_start') is-invalid @enderror"
                               required>
                        
                        @error('year_start')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </form>
            </div>

            <div class="modal-footer border-0">
                <button type="submit" form="formCrearProyecto" class="btn btn-success fw-bold rounded-3 shadow-sm" 
                        onclick="this.disabled=true; this.innerText='Creando...'; this.form.submit();">
                    Crear Proyecto
                </button>
                <button type="button" class="btn btn-danger fw-bold rounded-3 shadow-sm" data-bs-dismiss="modal">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Script para reabrir el modal si hay errores después de la redirección --}}
@if ($errors->has('year_start') && old('proyecto_action') == 'crear')
    <script>
        $(document).ready(function() {
            $('#crearProyectoModal').modal('show');
        });
    </script>
@endif