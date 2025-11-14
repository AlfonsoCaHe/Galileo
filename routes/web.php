<?php

use App\Http\Controllers\ProyectoController;
use App\Http\Controllers\ProfesorController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/listado-bases-de-datos', [ProyectoController::class, 'index'])->name('proyectos.listado');

Route::get('/profesores', [ProfesorController::class, 'indexProfesores'])->name('profesor.index');
Route::get('/profesor/{profesor_id}/alumnos', [ProfesorController::class, 'mostrarAlumnos'])->name('profesor.alumnos');