<?php

use App\Http\Controllers\ProfesorController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\UsuariosController;
use App\Http\Controllers\TutorLaboralController;
use App\Http\Controllers\ModuloController;
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
});
//----------------------------------Rutas alumnos----------------------------------------------------//
Route::middleware(['auth', AlumnoCheck::class])->group(function () {
    Route::get('/alumnos/panel', function () {
        // En una aplicación real, aquí retornarías la vista específica del alumno
        return view('alumno.panel'); 
    })->name('alumno.panel'); 

});

//----------------------------------Rutas profesores----------------------------------------------------//
Route::middleware(['auth', ProfesorCheck::class])->group(function () {
    Route::get('/profesor/panel', function () {
        return view('profesor.panel'); 
    })->name('profesor.panel'); 

});


//----------------------------------Rutas tutores laborales--------------------------------------------//
Route::middleware(['auth', TutorLaboralCheck::class])->group(function () {
    Route::get('/tutores/panel', function () {
        return view('tutores.panel'); 
    })->name('tutores.panel');

    Route::get('/tutores', [TutorLaboralController::class, 'indexTutoresLaborales'])->name('tutores.index');

    // Ruta para ver los alumnos del tutor
    //Aprovecha la vista alumnos.index
    Route::get('/tutores/alumnos', [TutorLaboralController::class, 'mostrarAlumnos'])->name('tutores.alumnos');

});

//----------------------------------Rutas administrador----------------------------------------------------//
// Rutas solo será accesibles si el usuario está logueado Y tiene rol='admin'
Route::middleware(['auth', AdminCheck::class])->group(function () {
    Route::get('/admin/panel', function () {return view('admin.panel');})->name('admin.panel');// Muestra la vista admin/panel.blade.php
    
    Route::get('/gestion/proyectos', [AdminController::class, 'listadoProyectos'])->name('gestion.proyectos.index');

    //Ruta para crear una nueva base de datos pulsando el componente /resources/views/admin/crear-proyecto-form.blade.php
    Route::post('/admin/crear-proyecto', [AdminController::class, 'crearProyecto'])->name('admin.crear.proyecto');

    //Ruta para ver el listado total de alumnos de los proyectos visibles
    Route::get('/alumnos', [AlumnoController::class, 'listadoVisibles'])->name('alumno.listadoVisibles');

    //Ruta para ver el listado de alumnos de un proyecto concreto
    Route::get('alumnos/{proyecto_id}/alumnos', [AlumnoController::class, 'listadoAlumnosProyecto'])->name('admin.alumnosProyecto');

    //Ruta para ver el formulario para agregar un nuevo profesor
    Route::get('gestion/profesor/crear', [UsuariosController::class, 'createProfesor'])->name('gestion.profesor.crear');
    //Ruta para ver el formulario para agregar un nuevo alumno
    Route::get('gestion/alumno/crear', [UsuariosController::class, 'createAlumno'])->name('gestion.alumno.crear');
    //Ruta para insertar un nuevo usuario
    Route::post('usuarios/store', [UsuariosController::class, 'store'])->name('usuarios.store');
    //Ruta para mostrar el listado de usuarios del sistema
    Route::get('usuarios/show', [UsuariosController::class, 'show'])->name('usuarios.show');

    //Ruta para mostrar el listado de usuarios mediante DataTable
    Route::post('/usuarios/showDataTable',[UsuariosController::class, 'showDataTable'])->name('usuarios.showDataTable');
    //Ruta para eliminar un usuario
    Route::post('/usuarios/eliminar', [UsuariosController::class, 'eliminar'])->name('usuarios.eliminar');

    // Ruta para mostrar el formulario de edición de usuario
    Route::get('usuarios/{id}/edit', [UsuariosController::class, 'edit'])->name('usuarios.editar');
    // Ruta para procesar la actualización del usuario
    Route::post('usuarios/{id}/update', [UsuariosController::class, 'update'])->name('usuarios.update');

    // Ruta para mostrar el listado de empresas mediante data tables
    Route::get('/gestion/empresas', [TutorLaboralController::class, 'indexEmpresas'])
        ->name('gestion.empresas.index');
    // Ruta para mostrar el formulario para crear una Empresa nueva y sus tutores
    Route::get('/gestion/empresas/crear', [TutorLaboralController::class, 'createEmpresa'])
        ->name('gestion.empresas.create');
    // Ruta que almacena la información del formnulario para crear una Empresa nueva
    Route::post('/gestion/empresas', [TutorLaboralController::class, 'storeEmpresa'])
        ->name('gestion.empresas.store');
    // Ruta para mostrar el formulario de edición de empresas
    Route::get('/gestionempresas/{empresa_id}/editar', [TutorLaboralController::class, 'editEmpresa'])
        ->name('gestion.empresas.edit');
    // Ruta para actualizar la modificación de la empresa
    Route::put('/empresas/{empresa_id}', [TutorLaboralController::class, 'updateEmpresa'])
        ->name('gestion.empresas.update');
    // Ruta para eliminar una empresa
    Route::delete('/gestion/empresas/{empresa_id}', [TutorLaboralController::class, 'destroyEmpresa'])
        ->name('gestion.empresas.destroy');
    // Ruta para añadir un nuevo tutor a la empresa desde datatable
    Route::post('/gestion/empresas/{empresa_id}/tutores', [TutorLaboralController::class, 'storeTutor'])
        ->name('gestion.tutores.store');
    // Ruta que muestra el formulario para editar un tutor
    Route::get('/gestion/tutores/{tutor_id}/edit', [TutorLaboralController::class, 'editTutor'])
        ->name('gestion.tutores.edit');
    // Ruta para actualizar un tutor existente desde datatable
    Route::put('/gestion/tutores/{tutor_id}', [TutorLaboralController::class, 'updateTutor'])
        ->name('gestion.tutores.update');
    // Ruta para eliminar un tutor
    Route::delete('/gestion/tutores/{tutor_id}', [TutorLaboralController::class, 'destroyTutor'])
        ->name('gestion.tutores.destroy');
    //Ruta que muestra el formulario para crear un tutor asociado a una empresa específica desde el datatable
    Route::get('/gestion/empresas/{empresa_id}/tutores/create', [TutorLaboralController::class, 'createTutor'])
        ->name('gestion.tutores.create');

    // --- Rutas CRUD para Módulos (Requieren ID de Proyecto para conexión) ---
    Route::prefix('gestion/proyectos/{proyecto_id}/modulos')->group(function () {
    
        // READ (Index)
        Route::get('/', [ModuloController::class, 'index'])
            ->name('gestion.modulos.index');

        // CREATE (Formulario)
        Route::get('/create', [ModuloController::class, 'create'])
            ->name('gestion.modulos.create');
            
        // STORE (Guardar nuevo)
        Route::post('/', [ModuloController::class, 'store'])
            ->name('gestion.modulos.store');

        // EDIT (Formulario, requiere ID de Módulo)
        Route::get('/{modulo_id}/edit', [ModuloController::class, 'edit'])
            ->name('gestion.modulos.edit');

        // UPDATE (Actualizar, requiere ID de Módulo)
        Route::put('/{modulo_id}', [ModuloController::class, 'update'])
            ->name('gestion.modulos.update');

        // DELETE (Eliminar, requiere ID de Módulo)
        Route::delete('/{modulo_id}', [ModuloController::class, 'destroy'])
            ->name('gestion.modulos.destroy');
    });
});