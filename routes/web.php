<?php

use App\Http\Controllers\ProyectoController;
use App\Http\Controllers\ProfesorController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\UsuariosController;
use App\Http\Middleware\AdminCheck;
use App\Http\Middleware\AlumnoCheck;
use App\Http\Middleware\ProfesorCheck;
use App\Http\Middleware\TutorLaboralCheck;
use App\Http\Middleware\SetProjectConnection;
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

// --------------------------------Rutas Protegidas (Requieren Rol)------------------------------------//
Route::middleware(['auth'])->group(function () {
    
    // Redirige al panel específico según el rol.
    Route::get('/home', [UsuariosController::class, 'redirectToPanel'])->name('home');

    Route::get('/alumno/{alumno_id}', [AlumnoController::class, 'showAlumno'])->middleware(SetProjectConnection::class)->name('alumno.show');

    //Rutas sin ordenar todavía
    Route::get('/profesores', [ProfesorController::class, 'indexProfesores'])->name('profesor.index');
    Route::get('/profesor/{profesor_id}/alumnos', [ProfesorController::class, 'mostrarAlumnos'])->middleware(SetProjectConnection::class)->name('profesor.alumnos');
});
//----------------------------------Rutas alumnos----------------------------------------------------//
Route::middleware(['auth', AlumnoCheck::class, SetProjectConnection::class])->group(function () {
    Route::get('/alumnos/panel', function () { return view('alumno.panel');})->name('alumno.panel'); 

});

//----------------------------------Rutas profesores----------------------------------------------------//
Route::middleware(['auth', ProfesorCheck::class])->group(function () {
    Route::get('/profesor/panel', function () { return view('profesor.panel'); })->name('profesor.panel'); 

});


//----------------------------------Rutas tutores laborales--------------------------------------------//
Route::middleware(['auth', TutorLaboralCheck::class])->group(function () {
    Route::get('/tutores/panel', function () { return view('tutores.panel'); })->name('tutores.panel'); 

});


//----------------------------------Rutas administrador----------------------------------------------------//
// Rutas solo será accesibles si el usuario está logueado Y tiene rol='admin'
Route::middleware(['auth', AdminCheck::class])->group(function () {
    Route::get('/admin/panel', function () {return view('admin.panel');})->name('admin.panel');// Muestra la vista admin/panel.blade.php
    
    Route::get('/listado-proyectos', [AdminController::class, 'listadoProyectos'])->name('admin.proyectos');

    //Ruta para crear una nueva base de datos pulsando el componente /resources/views/admin/crear-proyecto-form.blade.php
    Route::post('/admin/crear-proyecto', [AdminController::class, 'crearProyecto'])->name('admin.crear.proyecto');

    //Ruta para ver el listado total de alumnos de los proyectos visibles
    Route::get('/alumnos', [AlumnoController::class, 'listadoVisibles'])->middleware(SetProjectConnection::class)->name('alumno.listadoVisibles');

    //Ruta para ver el listado de alumnos de un proyecto concreto
    Route::get('alumnos/{proyecto_id}/alumnos', [AlumnoController::class, 'listadoAlumnosProyecto'])->middleware(SetProjectConnection::class)->name('admin.alumnosProyecto');

});