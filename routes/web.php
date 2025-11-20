<?php

use App\Http\Controllers\ProyectoController;
use App\Http\Controllers\ProfesorController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AlumnoController;
use App\Http\Controllers\UsuariosController;
use App\Http\Middleware\AdminCheck;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('usuarios.welcome');
});

Route::get('/listado-bases-de-datos', [ProyectoController::class, 'index'])->name('proyectos.listado');

Route::get('/profesores', [ProfesorController::class, 'indexProfesores'])->name('profesor.index');
Route::get('/profesor/{profesor_id}/alumnos', [ProfesorController::class, 'mostrarAlumnos'])->name('profesor.alumnos');

Route::get('/alumnos', [AlumnoController::class, 'indexAlumno'])->name('alumno.index');
Route::get('/alumno/{alumno_id}', [AlumnoController::class, 'showAlumno'])->name('alumno.show');

Route::get('/admin/panel', function () {return view('admin.panel');})->name('admin.panel');

//Ruta para crear una nueva base de datos pulsando el componente /resources/views/admin/crear-proyecto-form.blade.php
Route::post('/admin/crear-proyecto', [AdminController::class, 'crearProyecto'])->name('admin.crear.proyecto');

// --------------------------------Rutas de acceso de usuarios-------------------------------------------//
// Muestra el formulario de login
Route::get('/login', [UsuariosController::class, 'showLoginForm'])->name('login');
// Procesa el login
Route::post('/login', [UsuariosController::class, 'login']);
// Cierra la sesión
Route::post('/logout', [UsuariosController::class, 'logout'])->name('logout');


//----------------------------------Rutas restringidas----------------------------------------------------//
// Grupo de rutas que requiere autenticación (para todos los usuarios)
Route::middleware(['auth'])->group(function () {
    // Ruta de inicio genérica para usuarios logueados
    Route::get('/home', function () {
        // En una aplicación real, aquí redirigirías según el rol.
        return '¡Estás logueado! Esta es la página HOME.';
    })->name('home');
});


// Ruta protegida para ADMINISTRADORES
Route::middleware(['auth', AdminCheck::class])->group(function () {
    
    // Esta ruta ahora solo será accesible si el usuario está logueado Y tiene rol='admin'
    Route::get('/admin/panel', function () {
        return view('admin.panel'); // Muestra la vista admin/panel.blade.php
    })->name('admin.panel');
    
    // Aquí puedes añadir más rutas exclusivas para administradores
});