<?php

use App\Http\Controllers\ProyectoController;
use App\Http\Controllers\ProfesorController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AlumnoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/listado-bases-de-datos', [ProyectoController::class, 'index'])->name('proyectos.listado');

Route::get('/profesores', [ProfesorController::class, 'indexProfesores'])->name('profesor.index');
Route::get('/profesor/{profesor_id}/alumnos', [ProfesorController::class, 'mostrarAlumnos'])->name('profesor.alumnos');

Route::get('/alumnos', [AlumnoController::class, 'indexAlumno'])->name('alumno.index');
Route::get('/alumno/{alumno_id}', [AlumnoController::class, 'showAlumno'])->name('alumno.show');

Route::get('/admin/panel', function () {return view('admin.panel');})->name('admin.panel');

//Ruta para crear una nueva base de datos pulsando el componente /resources/views/admin/crear-proyecto-form.blade.php
Route::post('/admin/crear-proyecto', [AdminController::class, 'crearProyecto'])->name('admin.crear.proyecto');