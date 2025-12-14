<div class="container-fluid p-3 border-bottom">
    <div class="row align-items-center g-3">
        
        {{-- LOGO (Izquierda en escritorio, centrado en móvil) --}}
        <div class="col-12 col-xl-auto text-center">
            <a href="{{ route('home') }}" class="d-inline-block rounded-circle shadow-sm p-1 border">
                <img src="{{ asset('img/logo_galileo_f.png') }}" class="rounded-circle" style="width: min(5rem, 8rem); height: min(5rem, 8rem); object-fit: cover;" alt="IES GALILEO"/>
            </a>
        </div>

        {{-- CONTENEDOR DE NAVEGACIÓN --}}
        <div class="col-12 col-xl">
            
            {{-- ============================================================== --}}
            {{-- VERSIÓN ESCRITORIO (Visible solo en LG y superiores)           --}}
            {{-- ============================================================== --}}
            <div class="d-none d-lg-block">
                <div class="row g-2 justify-content-around">
                    
                    {{-- Botón Módulos --}}
                    <div class="col-lg-3 col-xxl-2">
                        <a href="{{ route('profesores.modulos') }}" class="btn btn-primary w-100 h-100 p-3 shadow-sm d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-mortarboard-fill fs-2 mb-1"></i>
                            <span class="fw-bold text-uppercase small">Módulos</span>
                        </a>
                    </div>

                    {{-- Botón Tutor Docente --}}
                    <div class="col-lg-3 col-xxl-2">
                        <a href="{{ route('profesores.tutorizados') }}" class="btn btn-primary w-100 h-100 p-3 shadow-sm d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-people-fill fs-2 mb-1"></i>
                            <span class="fw-bold text-uppercase small">Tutor Docente</span>
                        </a>
                    </div>

                    {{-- Botón Datos Personales --}}
                    <div class="col-lg-3 col-xxl-2">
                        <a href="{{ route('profesores.editar', auth()->user()->rolable) }}" 
                           class="btn btn-primary w-100 h-100 p-3 shadow-sm d-flex flex-column align-items-center justify-content-center text-decoration-none">
                            <i class="bi bi-person-circle fs-2 mb-1"></i>
                            <span class="fw-bold text-uppercase small">Datos Personales</span>
                        </a>
                    </div>

                    {{-- Botón Cerrar Sesión --}}
                    <div class="col-lg-3 col-xxl-2">
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

            {{-- ============================================================== --}}
            {{-- VERSIÓN MÓVIL (Visible solo en pantallas menores a LG)         --}}
            {{-- ============================================================== --}}
            <div class="d-lg-none">
                <div class="dropdown">
                    <button class="btn btn-success w-100 d-flex justify-content-between align-items-center p-3 shadow-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <span class="fw-bold text-uppercase"><i class="bi bi-list me-2"></i>Menú Profesores</span>
                    </button>
                    <ul class="dropdown-menu w-100 shadow border-0 mt-1">
                        {{-- Módulos --}}
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('profesores.modulos') }}">
                                <i class="bi bi-mortarboard-fill me-2 text-primary"></i> Módulos
                            </a>
                        </li>
                        
                        {{-- Tutor Docente --}}
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('profesores.tutorizados') }}">
                                <i class="bi bi-people-fill me-2 text-primary"></i> Tutor Docente
                            </a>
                        </li>

                        {{-- Datos Personales --}}
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('profesores.editar', auth()->user()->rolable) }}">
                                <i class="bi bi-person-circle me-2 text-primary"></i> Datos Personales
                            </a>
                        </li>

                        <li><hr class="dropdown-divider"></li>
                        
                        {{-- Cerrar Sesión --}}
                        <li>
                            <form action="{{ route('logout') }}" method="POST">
                                @csrf
                                <button type="submit" class="dropdown-item py-2 text-danger fw-bold">
                                    <i class="bi bi-box-arrow-right me-2"></i> Cerrar Sesión
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>
</div>