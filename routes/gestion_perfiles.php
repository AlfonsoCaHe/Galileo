<?php

use App\Http\Controllers\ProfesoradoDocenteController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ProfesorCheck;
use App\Http\Middleware\SetProjectConnection; 

Route::middleware(['auth'])->group(function () {
    
    // Rutas del profesor
    // Aplicamos ProfesorCheck para seguridad y SetProjectConnection para contexto de BD
    Route::middleware([ProfesorCheck::class, SetProjectConnection::class])->group(function () {
        
        // Panel principal (se definirá al final)
        Route::get('/profesor/panel', function () {
            return view('profesores.panel'); 
        })->name('profesores.panel');

        // --- GESTIÓN DOCENTE ---

        // Listado de Módulos
        Route::get('/profesor/modulos', [ProfesoradoDocenteController::class, 'indexModulos'])
            ->name('profesores.modulos');

        // Ver Alumnos del módulo (Placeholder para la siguiente fase)
        Route::get('/profesor/{proyecto_id}/modulos/{modulo_id}/alumnos', [ProfesoradoDocenteController::class, 'verAlumnos'])
            ->name('profesores.modulos.alumnos');

        Route::get('/profesor/{id}/editar', [ProfesoradoDocenteController::class, 'editar'])
            ->name('profesores.editar');

        Route::put('/profesor/{id}/update', [ProfesoradoDocenteController::class, 'update'])
            ->name('profesores.update');

        // Ruta para ver tareas de un alumno
        Route::get('/profesor/proyecto/{proyecto_id}/modulos/{modulo_id}/alumnos/{alumno_id}/tareas', [ProfesoradoDocenteController::class, 'verTareasAlumno'])
            ->name('profesores.alumnos.tareas');

        //Ruta para ver los alumnos de los que se es tutor docente
        Route::get('/tutor_docente/alumnos', [ProfesoradoDocenteController::class, 'tutorizados'])
            ->name('profesores.tutorizados');

        //Ruta para ver las tareas de un alumno tutorizado
        Route::get('/tutor_docente/{proyecto_id}/alumno/{alumno_id}/tareas', [ProfesoradoDocenteController::class, 'tareasAlumnoTutorizado'])
            ->name('profesores.tutorizados.tareas');

    });
});