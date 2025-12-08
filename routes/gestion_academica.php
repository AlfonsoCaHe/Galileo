<?php

use App\Http\Middleware\AdminCheck;
use App\Http\Middleware\AlumnoCheck;
use App\Http\Middleware\ProfesorCheck;
use App\Http\Middleware\TutorLaboralCheck;
use App\Http\Controllers\CriterioController;
use App\Http\Controllers\RaController;
use App\Http\Controllers\TareaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Gestión Académica (RAs, Criterios, Tareas)
|--------------------------------------------------------------------------
|
| Estas rutas manejan la lógica profunda de los proyectos.
| Todas dependen del ID del Proyecto para establecer la conexión dinámica.
| Prefijo general: /gestion/proyectos/{proyecto_id}
|
*/

Route::middleware(['auth'])->prefix('gestion/proyectos/{proyecto_id}')->group(function () {

    // =============================================================
    // 1. GESTIÓN DE RAs (Resultados de Aprendizaje)
    // Dependen de un Módulo específico
    // =============================================================
    Route::prefix('modulos/{modulo_id}')->group(function () {
        
        // Listar RAs de un módulo
        Route::get('/ras', [RaController::class, 'index'])
            ->name('gestion.ras.index');

        // Formulario Crear RA
        Route::get('/ras/create', [RaController::class, 'create'])
            ->name('gestion.ras.create');

        // Guardar RA
        Route::post('/ras', [RaController::class, 'store'])
            ->name('gestion.ras.store');
    });

    // Rutas de RA que no necesitan el ID del módulo en la URL (porque ya tenemos el ID del RA)
    Route::prefix('ras/{ra_id}')->group(function () {
        // Editar RA
        Route::get('/edit', [RaController::class, 'edit'])
            ->name('gestion.ras.edit');
        
        // Actualizar RA
        Route::put('/', [RaController::class, 'update'])
            ->name('gestion.ras.update');
        
        // Eliminar RA
        Route::delete('/', [RaController::class, 'destroy'])
            ->name('gestion.ras.destroy');

        // =============================================================
        // 2. GESTIÓN DE CRITERIOS
        // Dependen de un RA específico (Nidificación profunda)
        // =============================================================
        
        // Listar Criterios de un RA (Normalmente se ven en el index de RAs, pero por si acaso)
        Route::get('/criterios', [CriterioController::class, 'index'])
            ->name('gestion.criterios.index');

        // Crear Criterio para este RA
        Route::post('/criterios', [CriterioController::class, 'store'])
            ->name('gestion.criterios.store');
    });

    // Rutas de Criterio individuales (Editar/Eliminar)
    Route::prefix('criterios/{criterio_id}')->group(function () {
        Route::get('/edit', [CriterioController::class, 'edit'])
            ->name('gestion.criterios.edit');
            
        Route::put('/', [CriterioController::class, 'update'])
            ->name('gestion.criterios.update');
            
        Route::delete('/', [CriterioController::class, 'destroy'])
            ->name('gestion.criterios.destroy');
    });

    // =============================================================
    // 3. GESTIÓN DE TAREAS
    // Dependen de un Módulo (El profesor asigna tareas dentro de un módulo)
    // =============================================================
    Route::prefix('modulos/{modulo_id}')->group(function () {
        
        // Listar Tareas del módulo
        Route::get('/tareas', [TareaController::class, 'index'])
            ->name('gestion.tareas.index');

        // Formulario Crear Tarea (Aquí seleccionaremos alumnos con checkboxes)
        Route::get('/tareas/create', [TareaController::class, 'create'])
            ->name('gestion.tareas.create');

        // Guardar Tarea (Generación masiva para N alumnos)
        Route::post('/tareas', [TareaController::class, 'store'])
            ->name('gestion.tareas.store');
        
        // Editar definición de la tarea (Solo el profesor)
        Route::get('tareas/{tarea_id}/edit', [TareaController::class, 'edit'])
            ->name('gestion.tareas.edit');
    });

    // =============================================================
    // 4. Gestión de Tareas
    // Dependen de la tarea
    // =============================================================
    Route::prefix('tareas/{tarea_id}')->group(function () {
        
        // Ver detalle / Evaluar (Tutor Laboral y Profesor)
        Route::get('/', [TareaController::class, 'show'])
            ->name('gestion.tareas.show');
        
        // Actualizar datos o calificación
        Route::put('/', [TareaController::class, 'update'])
            ->name('gestion.tareas.update');

        // Eliminar tarea (solo si no está evaluada, por seguridad)
        Route::delete('/', [TareaController::class, 'destroy'])
            ->name('gestion.tareas.destroy');

        // Bloquea o desbloquea la edición de una tarea (solo el profesor)
        Route::put('/toggle-bloqueo', [TareaController::class, 'toggleBloqueo'])
            ->name('gestion.tareas.toggleBloqueo');

        // Peticiones AJAX
        Route::put('/update-fecha', [TareaController::class, 'updateFecha'])
            ->name('gestion.tareas.updateFecha');
        Route::put('/update-duracion', [TareaController::class, 'updateDuracion'])
            ->name('gestion.tareas.updateDuracion');
        Route::put('/bloqueo-masivo', [TareaController::class, 'toggleBloqueoMasivo'])
            ->name('gestion.tareas.toggleBloqueoMasivo');
        Route::put('/update-apto', [TareaController::class, 'updateApto'])
            ->name('gestion.tareas.updateApto');
        Route::put('/update-bloqueo', [TareaController::class, 'updateBloqueo'])
            ->name('gestion.tareas.updateBloqueo');
    });

});