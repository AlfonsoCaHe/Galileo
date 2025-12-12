<div class="container-fluid p-3 border-bottom">
    <div class="row align-items-center g-3">
        
        {{-- LOGO (Izquierda en escritorio, centrado en móvil) --}}
        <div class="col-12 col-xl-auto text-center">
            <a href="{{ route('home') }}" class="d-inline-block rounded-circle shadow-sm p-1 border">
                <img src="{{ asset('img/logo_galileo_f.png') }}" class="rounded-circle" style="width: min(5rem, 8rem); height: min(5rem, 8rem); object-fit: cover;" alt="IES GALILEO"/>
            </a>
        </div>

        {{-- BOTONES (Ocupan el resto del espacio) --}}
        <div class="col-12 col-xl">
            <div class="row g-2 justify-content-around">
                {{-- 
                   Configuración de la rejilla de botones:
                   col-6:    Móviles (2 botones por fila)
                   col-md-4: Tablets (3 botones por fila)
                   col-xxl-2: Pantallas muy grandes (6 botones en una sola fila)
                --}}
                
                <div class="col-6 col-md-4 col-xxl-2">
                    <a href="{{ route('profesores.modulos') }}" class="btn btn-primary w-100 h-100 p-3 shadow-sm d-flex flex-column align-items-center justify-content-center">
                        <i class="bi bi-mortarboard-fill fs-2 mb-1"></i>
                        <span class="fw-bold text-uppercase small">Módulos</span>
                    </a>
                </div>

                <div class="col-6 col-md-4 col-xxl-2">
                    <a href="{{ route('profesores.tutorizados') }}" class="btn btn-primary w-100 h-100 p-3 shadow-sm d-flex flex-column align-items-center justify-content-center">
                        <i class="bi bi-mortarboard-fill fs-2 mb-1"></i>
                        <span class="fw-bold text-uppercase small">Tutor Docente</span>
                    </a>
                </div>

                <div class="col-6 col-md-4 col-xxl-2">
                    <a href="{{ route('profesores.editar', auth()->user()->rolable) }}" 
                    class="btn btn-primary w-100 h-100 p-3 shadow-sm d-flex flex-column align-items-center justify-content-center text-decoration-none">
                        
                        <i class="bi bi-mortarboard-fill fs-2 mb-1"></i>
                        <span class="fw-bold text-uppercase small">Datos Personales</span>
                    </a>
                </div>

                <div class="col-6 col-md-4 col-xxl-2">
                    <form action="{{ route('logout') }}" method="POST" class="h-100">
                        @csrf
                        <button type="submit" class="btn btn-danger w-100 h-100 p-3 shadow-sm d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-box-arrow-right fs-2 mb-1"></i>
                            <span class="fw-bold text-uppercase small">Cerrar Sesión</span>
                        </button>
                    </form>
                </div>

            </div>
        </div>
    </div>
</div>