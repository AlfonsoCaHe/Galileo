@extends('layouts.default')

@section('title', 'Gestión de RAs y Criterios')

@section('scripts')
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if(session('open_ra'))
            var collapseElement = document.getElementById('collapse-{{ session("open_ra") }}');
            if (collapseElement) {
                var bsCollapse = new bootstrap.Collapse(collapseElement, { toggle: true });
                collapseElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
    });
</script>
@endsection

@section('content')
@include('gestion.layouts.header')
<div class="container-fluid">
    
    {{-- CABECERA --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class=" mb-0 texto">Resultados de Aprendizaje</h2>
            <p class="fs-6 texto mb-0 mt-1">
                Módulo: <strong class="text-warning">{{ $modulo->nombre }}</strong>
            </p>
        </div>
        <a href="javascript:history.back()" class="btn btn-danger shadow-sm">
            <i class="bi bi-arrow-left me-2"></i>Volver
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

    {{-- ZONA DE CREACIÓN DE NUEVO RA --}}
    <div class="card shadow mb-4 border-left-primary">
        <div class="card-body">
            <form action="{{ route('gestion.ras.store', ['proyecto_id' => $proyecto_id, 'modulo_id' => $modulo->id_modulo]) }}" method="POST" class="row g-3 align-items-end">
                @csrf
                
                {{-- CAMPO CÓDIGO (RA1) --}}
                <div class="col-md-2">
                    <label for="codigo" class="form-label fw-bold text-primary">Código</label>
                    <input type="text" class="form-control" id="codigo" name="codigo" placeholder="Ej: RA1" required>
                </div>

                {{-- CAMPO DESCRIPCIÓN --}}
                <div class="col-md-8">
                    <label for="descripcion" class="form-label fw-bold text-primary">Descripción del Resultado</label>
                    <input type="text" class="form-control" id="descripcion" name="descripcion" placeholder="Identifica los componentes..." required>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-success w-100 fw-bold shadow-sm">
                        <i class="bi bi-plus-lg me-1"></i> Añadir
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- LISTADO EN ACORDEÓN --}}
    @if($modulo->ras->isEmpty())
        <div class="alert alert-warning text-center py-4">
            <i class="bi bi-info-circle fs-4 d-block mb-2"></i>
            No hay Resultados de Aprendizaje definidos para este módulo.
        </div>
    @else
        <div class="accordion shadow-sm" id="accordionRas">
            @foreach($modulo->ras as $ra)
                <div class="accordion-item mb-2 border rounded overflow-hidden">
                    
                    {{-- CABECERA DEL ACORDEÓN --}}
                    <h2 class="accordion-header" id="heading-{{ $ra->id_ras }}">
                        <button class="accordion-button {{ session('open_ra') == $ra->id_ras ? '' : 'collapsed' }} fw-bold text-dark bg-light" 
                                type="button" 
                                data-bs-toggle="collapse" 
                                data-bs-target="#collapse-{{ $ra->id_ras }}" 
                                aria-expanded="{{ session('open_ra') == $ra->id_ras ? 'true' : 'false' }}" 
                                aria-controls="collapse-{{ $ra->id_ras }}">
                            
                            {{-- Mostramos Código en Badge y Descripción al lado --}}
                            <span class="badge bg-primary me-3">{{ $ra->codigo }}</span>
                            {{ $ra->descripcion }}
                        </button>
                    </h2>

                    {{-- CUERPO DEL ACORDEÓN --}}
                    <div id="collapse-{{ $ra->id_ras }}" 
                         class="accordion-collapse collapse {{ session('open_ra') == $ra->id_ras ? 'show' : '' }}" 
                         aria-labelledby="heading-{{ $ra->id_ras }}" 
                         data-bs-parent="#accordionRas">
                        
                        <div class="accordion-body bg-white">
                            
                            <div class="d-flex justify-content-end mb-3 border-bottom pb-2">
                                <form action="{{ route('gestion.ras.destroy', ['proyecto_id' => $proyecto_id, 'ra_id' => $ra->id_ras]) }}" 
                                      method="POST" 
                                      onsubmit="return confirm('¿Eliminar el {{ $ra->codigo }} y todos sus criterios?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">
                                        <i class="bi bi-trash-fill me-1"></i> Eliminar RA
                                    </button>
                                </form>
                            </div>

                            <h6 class="text-secondary fw-bold mb-3">Criterios de Evaluación:</h6>

                            {{-- TABLA DE CRITERIOS --}}
                            <div class="table-responsive mb-4">
                                <table class="table table-sm table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 80px;">Cod.</th>
                                            <th>Descripción del Criterio</th>
                                            <th class="text-center" style="width: 100px;">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($ra->criterios as $criterio)
                                            <tr>
                                                <td class="fw-bold text-center text-primary bg-light">{{ $criterio->ce }}</td>
                                                <td>{{ $criterio->descripcion }}</td>
                                                <td class="text-center">
                                                    <form action="{{ route('gestion.criterios.destroy', ['proyecto_id' => $proyecto_id, 'criterio_id' => $criterio->id_criterio]) }}" method="POST">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-link text-danger p-0">
                                                            <i class="bi bi-x-circle-fill fs-5"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted fst-italic py-3">
                                                    No hay criterios definidos.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            {{-- FORMULARIO AÑADIR CRITERIO --}}
                            <div class="bg-light p-3 rounded border border-dashed">
                                <form action="{{ route('gestion.criterios.store', ['proyecto_id' => $proyecto_id, 'ra_id' => $ra->id_ras]) }}" method="POST">
                                    @csrf
                                    <div class="row g-2">
                                        <div class="col-md-2">
                                            <input type="text" class="form-control form-control-sm" name="ce" placeholder="Ej: a)" required>
                                        </div>
                                        <div class="col-md-8">
                                            <input type="text" class="form-control form-control-sm" name="descripcion" placeholder="Descripción del criterio..." required>
                                        </div>
                                        <div class="col-md-2">
                                            <button type="submit" class="btn btn-sm btn-success w-100 fw-bold">
                                                <i class="bi bi-plus-circle me-1"></i> Añadir
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection