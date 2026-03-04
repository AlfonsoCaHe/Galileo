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
                    <div class="col-lg-2">
                        <a href="{{ url('/home') }}" class="btn btn-primary w-100 h-100 p-3 shadow-sm d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-house-door-fill fs-2 mb-1"></i>
                            <span class="fw-bold text-uppercase small">Inicio</span>
                        </a>
                    </div>
                    
                    <div class="col-lg-2">
                        <a href="{{ route('alumnado.tareas_realizadas', ['proyecto_id' => $proyecto->id_base_de_datos]) }}" class="btn btn-primary w-100 h-100 p-3 shadow-sm d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-check-circle-fill fs-2 mb-1"></i>
                            <span class="fw-bold text-uppercase small">Tareas Terminadas</span>
                        </a>
                    </div>

                    <div class="col-lg-2">
                        <a href="{{ route('alumnado.tareas_pendientes', ['proyecto_id' => $proyecto->id_base_de_datos]) }}" class="btn btn-primary w-100 h-100 p-3 shadow-sm d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-clock-history fs-2 mb-1"></i>
                            <span class="fw-bold text-uppercase small">Tareas</span>
                        </a>
                    </div>

                    <div class="col-lg-2">
                        <a href="{{ route('alumno.editar', ['proyecto_id' => $proyecto->id_base_de_datos]) }}" class="btn btn-primary w-100 h-100 p-3 shadow-sm d-flex flex-column align-items-center justify-content-center">
                            <i class="bi bi-person-lines-fill fs-2 mb-1"></i>
                            <span class="fw-bold text-uppercase small">Datos Personales</span>
                        </a>
                    </div>

                    <div class="col-lg-2">
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
                        <span class="fw-bold text-uppercase"><i class="bi bi-list me-2"></i>Menú de Opciones</span>
                    </button>
                    <ul class="dropdown-menu w-100 shadow border-0 mt-1">
                        <li>
                            <a class="dropdown-item py-2" href="{{ url('/home') }}">
                                <i class="bi bi-house-door-fill me-2 text-primary"></i> Inicio
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('alumnado.tareas_realizadas', ['proyecto_id' => $proyecto->id_base_de_datos]) }}">
                                <i class="bi bi-check-circle-fill me-2 text-primary"></i> Tareas Terminadas
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('alumnado.tareas_pendientes', ['proyecto_id' => $proyecto->id_base_de_datos]) }}">
                                <i class="bi bi-clock-history me-2 text-primary"></i> Tareas Sin Terminar
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item py-2" href="{{ route('alumno.editar', ['proyecto_id' => $proyecto->id_base_de_datos]) }}">
                                <i class="bi bi-person-lines-fill me-2 text-primary"></i> Datos Personales
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
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