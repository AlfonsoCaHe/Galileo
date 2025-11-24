<?php

use App\Http\Controllers\ProfesorController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\TutorLaboralController;
use App\Http\Controllers\ModuloController;
use App\Http\Controllers\ProyectoController;
use App\Http\Middleware\AdminCheck;
use App\Http\Middleware\AlumnoCheck;
use App\Http\Middleware\ProfesorCheck;
use App\Http\Middleware\TutorLaboralCheck;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('usuarios.login');
});

// --------------------------------Rutas genéricas de usuarios-------------------------------------------//
// Muestra el formulario de login
Route::get('/login', [UsuariosController::class, 'showLoginForm'])->name('login');
// Procesa el login
Route::post('/login', [UsuariosController::class, 'login']);
// Cierra la sesión
Route::post('/logout', [UsuariosController::class, 'logout'])->name('logout');

// --------------------------------Rutas Protegidas (Requieren Logueo)-------------------------------------//
Route::middleware(['auth'])->group(function () {
    
    // Redirige al panel específico según el rol.
    Route::get('/home', [UsuariosController::class, 'redirectToPanel'])->name('home');

    Route::get('/alumno/{alumno_id}', [AlumnoController::class, 'showAlumno'])->name('alumno.show');

    //Rutas sin ordenar todavía
    Route::get('/profesores', [ProfesorController::class, 'indexProfesores'])->name('profesor.index');
    Route::get('/profesor/{profesor_id}/alumnos', [ProfesorController::class, 'mostrarAlumnos'])->name('profesor.alumnos');

    //----------------------------------Rutas alumnos----------------------------------------------------//
    Route::middleware([AlumnoCheck::class])->group(function () {
        Route::get('/alumnos/panel', function () {
            // En una aplicación real, aquí retornarías la vista específica del alumno
            return view('alumno.panel'); 
        })->name('alumno.panel'); 

    });

    //----------------------------------Rutas profesores----------------------------------------------------//
    Route::middleware([ProfesorCheck::class])->group(function () {
        Route::get('/profesor/panel', function () {
            return view('profesor.panel'); 
        })->name('profesor.panel'); 

    });


    //----------------------------------Rutas tutores laborales--------------------------------------------//
    Route::middleware([TutorLaboralCheck::class])->group(function () {
        Route::get('/tutores/panel', function () {
            return view('tutores.panel'); 
        })->name('tutores.panel');

        Route::get('/tutores', [TutorLaboralController::class, 'indexTutoresLaborales'])->name('tutores.index');

        // Ruta para ver los alumnos del tutor
        //Aprovecha la vista alumnos.index
        Route::get('/tutores/alumnos', [TutorLaboralController::class, 'mostrarAlumnos'])->name('tutores.alumnos');

    });

    // --------------------------------- RUTAS SÓLO ADMINISTRADOR ------------------------------------//
    Route::middleware([AdminCheck::class])->group(function () {
        
        // --- 1. Panel de Administración y Proyectos ---
        Route::prefix('admin')->group(function () {
            // Panel principal
            Route::get('/panel', function () {
                return view('admin.panel');
            })->name('admin.panel');
            
            // Creación de Proyectos (Base de Datos Dinámica)
            Route::post('/crear-proyecto', [AdminController::class, 'crearProyecto'])
                ->name('admin.crear.proyecto');
        });

        // --- 2. Gestión de Usuarios del Sistema (Galileo) ---
        // Estas rutas están bajo el prefijo general 'usuarios' o 'gestion'
        Route::prefix('usuarios')->group(function () {
            // CRUD Básico (Crear/Guardar/Listar/Editar/Actualizar/Eliminar)
            Route::get('/show', [UsuariosController::class, 'show']) // Listado principal
                ->name('usuarios.show');
            Route::post('/store', [UsuariosController::class, 'store']) // Guardar nuevo usuario
                ->name('usuarios.store');
            Route::get('/{id}/edit', [UsuariosController::class, 'edit']) // Formulario de edición
                ->name('usuarios.editar');
            Route::post('/{id}/update', [UsuariosController::class, 'update']) // Procesar actualización
                ->name('usuarios.update');
            Route::post('/eliminar', [UsuariosController::class, 'eliminar']) // Eliminar usuario
                ->name('usuarios.eliminar');

            // DataTables y Creación por Rol
            Route::post('/showDataTable', [UsuariosController::class, 'showDataTable'])
                ->name('usuarios.showDataTable');
            Route::get('/gestion/profesor/crear', [UsuariosController::class, 'createProfesor'])
                ->name('gestion.profesor.crear');
        });

        Route::prefix('/gestion/alumnos')->group(function () {
            // Route::resource('/', AlumnoController::class)->except(['show']); // Opción más compacta si usas el prefijo
                
            Route::get('/', [AlumnoController::class, 'index'])
                ->name('gestion.alumnos.index');
            Route::post('/store', [AlumnoController::class, 'store'])
                ->name('gestion.alumnos.store');
            Route::get('/create', [AlumnoController::class, 'create'])
                ->name('gestion.alumnos.create');
            //Rutas con {alumno_id}
            Route::get('/{proyecto_id}/{alumno_id}/edit', [AlumnoController::class, 'edit'])
                ->name('gestion.alumnos.edit');
            Route::put('/{proyecto_id}/{alumno_id}/update', [AlumnoController::class, 'update'])
                ->name('gestion.alumnos.update');                
            Route::delete('/{proyecto_id}/{alumno_id}', [AlumnoController::class, 'destroy'])
                ->name('gestion.alumnos.destroy');
            Route::get('/proyecto/{proyecto_id}', [ProyectoController::class, 'index'])
                ->name('gestion.alumnos.proyecto');
        });

        // --- 3. Gestión de Proyectos, Módulos y Alumnos ---
        Route::prefix('gestion/proyectos')->group(function () {
            
            // CRUD de Proyectos
            Route::get('/', [ProyectoController::class, 'index'])
                ->name('gestion.proyectos.index');
            Route::post('/', [ProyectoController::class, 'store'])
                ->name('gestion.proyectos.store');
            Route::put('/{proyecto_id}/estado', [ProyectoController::class, 'updateEstado'])
                ->name('gestion.proyectos.update.estado');
            Route::delete('/{proyecto_id}', [ProyectoController::class, 'destroy'])
                ->name('gestion.proyectos.destroy');
        });
            
            // // Alumnos (Vistas/Listados)
            // Route::get('/alumnos', [AlumnoController::class, 'listadoVisibles']) // Listado total de todos los proyectos
            //     ->name('alumno.listadoVisibles');
            // Route::get('/proyectos/{proyecto_id}/alumnos', [AlumnoController::class, 'listadoAlumnosProyecto']) // Listado por proyecto
            //     ->name('admin.alumnosProyecto');
            
            

        // --- Rutas CRUD para Módulos (Requieren ID de Proyecto para conexión) ---
        Route::prefix('gestion/proyectos/{proyecto_id}/modulos')->group(function () {
            // Route::resource('/', ModuloController::class)->except(['show']); // Opción más compacta si usas el prefijo
                
            Route::get('/', [ModuloController::class, 'index'])
                ->name('gestion.modulos.index');
            Route::post('/', [ModuloController::class, 'store'])
                ->name('gestion.modulos.store');
            Route::get('/create', [ModuloController::class, 'create'])
                ->name('gestion.modulos.create');
            Route::get('/{modulo_id}/edit', [ModuloController::class, 'edit'])
                ->name('gestion.modulos.edit');
            Route::put('/{modulo_id}', [ModuloController::class, 'update'])
                ->name('gestion.modulos.update');
            Route::delete('/{modulo_id}', [ModuloController::class, 'destroy'])
                ->name('gestion.modulos.destroy');
        });
        

        // --- 4. Gestión de Empresas y Tutores Laborales (CRUD anidado) ---
        // Se pueden agrupar todas las rutas de TutorLaboralController bajo 'gestion/empresas'
        Route::prefix('gestion/empresas')->group(function () {
            
            // Rutas CRUD para Empresas
            Route::get('/', [TutorLaboralController::class, 'indexEmpresas'])
                ->name('gestion.empresas.index');
            Route::get('/crear', [TutorLaboralController::class, 'createEmpresa'])
                ->name('gestion.empresas.create');
            Route::post('/', [TutorLaboralController::class, 'storeEmpresa'])
                ->name('gestion.empresas.store');
            Route::get('/{empresa_id}/editar', [TutorLaboralController::class, 'editEmpresa']) // Se corrigió el prefijo original
                ->name('gestion.empresas.edit');
            Route::put('/{empresa_id}', [TutorLaboralController::class, 'updateEmpresa'])
                ->name('gestion.empresas.update');
            Route::delete('/{empresa_id}', [TutorLaboralController::class, 'destroyEmpresa'])
                ->name('gestion.empresas.destroy');

            // Rutas CRUD para Tutores (Anidadas o con rutas específicas)
            Route::post('/{empresa_id}/tutores', [TutorLaboralController::class, 'storeTutor']) // Almacenar nuevo tutor
                ->name('gestion.tutores.store');
            Route::get('/{empresa_id}/tutores/create', [TutorLaboralController::class, 'createTutor']) // Formulario de creación anidado
                ->name('gestion.tutores.create');
        });

        // Rutas de Tutores que no están anidadas por Empresa (solo por ID de Tutor)
        Route::prefix('gestion/tutores')->group(function () {
            Route::get('/{tutor_id}/edit', [TutorLaboralController::class, 'editTutor'])
                ->name('gestion.tutores.edit');
            Route::put('/{tutor_id}', [TutorLaboralController::class, 'updateTutor'])
                ->name('gestion.tutores.update');
            Route::delete('/{tutor_id}', [TutorLaboralController::class, 'destroyTutor'])
                ->name('gestion.tutores.destroy');
        });
    });
});