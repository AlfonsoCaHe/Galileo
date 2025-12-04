<div class="row m-2 g-3">
    
    <div class="d-flex">
        <div id="logo">
            <a href="{{ route('home') }}" class="btn w-100 h-100 p-1 d-flex flex-column align-items-center justify-content-center rounded-circle">
                <img src="{{ asset('img/logo_galileo_f.png') }}" class="rounded-circle" style="max-width: 90px; max-height: 90px;" alt="IES GALILEO"/>
            </a>
        </div>
        <div class="d-flex flex-row container w-auto justify-start">
            <div class="col-12 col-md-6 col-xl-3 m-1">
                <a href="{{ route('gestion.proyectos.index') }}" class="btn btn-primary w-100 h-100 p-4 shadow-sm d-flex flex-column align-items-center justify-content-center">
                    <i class="bi bi-people-fill fs-1 mb-2"></i>
                    <span class="fw-bold text-uppercase">{{ 'Proyectos' }}</span>
                </a>
            </div>
            <div class="col-12 col-md-6 col-xl-3 m-1">
                <a href="{{ route('gestion.profesores.index') }}" class="btn btn-primary w-100 h-100 p-4 shadow-sm d-flex flex-column align-items-center justify-content-center">
                    <i class="bi bi-people-fill fs-1 mb-2"></i>
                    <span class="fw-bold text-uppercase">{{ 'Profesores' }}</span>
                </a>
            </div>
            <div class="col-12 col-md-6 col-xl-3 m-1">
                <a href="{{ route('gestion.empresas.index') }}" class="btn btn-primary w-100 h-100 p-4 shadow-sm d-flex flex-column align-items-center justify-content-center">
                    <i class="bi bi-people-fill fs-1 mb-2"></i>
                    <span class="fw-bold text-uppercase">{{ 'Empresas' }}</span>
                </a>
            </div>
            <div class="col-12 col-md-6 col-xl-3 m-1">
                <a href="{{ route('gestion.alumnos.index') }}" class="btn btn-primary w-100 h-100 p-4 shadow-sm d-flex flex-column align-items-center justify-content-center">
                    <i class="bi bi-people-fill fs-1 mb-2"></i>
                    <span class="fw-bold text-uppercase">{{ 'Alumnos' }}</span>
                </a>
            </div>
            <div class="col-12 col-md-6 col-xl-3 m-1">
                <a href="{{ route('logout') }}"class="btn btn-danger w-100 h-100 p-4 shadow-sm d-flex flex-column align-items-center justify-content-center">
                    <i class="bi bi-people-fill fs-1 mb-2"></i>
                    <span class="fw-bold text-uppercase">{{ 'Cerrar Sesión' }}</span>
                </a>
            </div>
        </div>
    </div>
    <div><hr></div>
</div>