<?php

use App\Http\Controllers\ProyectoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/listado-bases-de-datos', [ProyectoController::class, 'index'])->name('proyectos.listado');
