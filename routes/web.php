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
});

require __DIR__ . '/gestion_academica.php';
require __DIR__ . '/gestion_administracion.php';