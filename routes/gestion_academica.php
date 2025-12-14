<?php

use App\Http\Middleware\AdminCheck;
use App\Http\Middleware\AlumnoCheck;
use App\Http\Middleware\ProfesorCheck;
use App\Http\Middleware\TutorLaboralCheck;
use App\Http\Controllers\CriterioController;
use App\Http\Controllers\RaController;
use App\Http\Controllers\ActividadesController;
use App\Http\Controllers\TareasController;
use App\Http\Controllers\AlumnoController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas de Gestión Académica (RAs, Criterios, Tareas y Actividades)
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
    // 3. GESTIÓN DE ACTIVIDADES
    // Dependen de un Módulo (El profesor crea actividades dentro de un módulo)
    // =============================================================
    Route::prefix('modulos/{modulo_id}')->group(function () {
        
        // Listar Actividades del módulo
        Route::get('/actividades', [ActividadesController::class, 'index'])
            ->name('gestion.actividades.index');

        // Formulario Crear Actividad
        Route::get('/actividades/create', [ActividadesController::class, 'create'])
            ->name('gestion.actividades.create');

        // Guardar Actividad
        Route::post('/actividades', [ActividadesController::class, 'store'])
            ->name('gestion.actividades.store');
        
        // Editar definición de la tarea (Solo el profesor)
        Route::get('/actividades/{actividad_id}/edit', [ActividadesController::class, 'edit'])
            ->name('gestion.actividades.edit');
        
        Route::put('/{actividad_id}/update', [ActividadesController::class, 'update'])
            ->name('gestion.actividades.update');

        Route::put('/{actividad_id}/destroy', [ActividadesController::class, 'destroy'])
            ->name('gestion.actividades.destroy');
    });

    // =============================================================
    // 4. Gestión de Tareas
    // Dependen de la tarea
    // =============================================================
    Route::prefix('tareas/{tarea_id}')->group(function () {
        
        // Ver detalle / Evaluar (Tutor Laboral y Profesor)
        Route::get('/', [TareasController::class, 'show'])
            ->name('gestion.tareas.show');
        
        // Actualizar tarea (solo si no está bloqueada)
        Route::put('/', [TareasController::class, 'updateTarea'])
            ->name('gestion.tareas.update');

        // Eliminar tarea (solo si no está evaluada, por seguridad)
        Route::delete('/', [TareasController::class, 'destroy'])
            ->name('gestion.tareas.destroy');

        // Bloquea o desbloquea la edición de una tarea (solo el profesor)
        Route::put('/toggle-bloqueo', [TareasController::class, 'toggleBloqueo'])
            ->name('gestion.tareas.toggleBloqueo');

        // Peticiones AJAX
        Route::put('/update-fecha', [TareasController::class, 'updateFecha'])
            ->name('gestion.tareas.updateFecha');
        Route::put('/update-duracion', [TareasController::class, 'updateDuracion'])
            ->name('gestion.tareas.updateDuracion');
        Route::put('/bloqueo-masivo', [TareasController::class, 'toggleBloqueoMasivo'])
            ->name('gestion.tareas.toggleBloqueoMasivo');
        Route::put('/update-apto', [TareasController::class, 'updateApto'])
            ->name('gestion.tareas.updateApto');
        Route::put('/update-bloqueo', [TareasController::class, 'updateBloqueo'])
            ->name('gestion.tareas.updateBloqueo');
        Route::put('update', [TareasController::class, 'updateNotas'])
            ->name('gestion.tareas.updateNotas');

        
    });

    // GESTIÓN DE ALUMNOS INDIVIDUAL
    Route::prefix('alumnos/{alumno_id}')->group(function () {
        
        Route::get('/', [AlumnoController::class, 'show'])->name('gestion.alumnos.show');
        
        // Ajax Tutores
        Route::put('/update-docente', [AlumnoController::class, 'updateTutorDocente'])->name('gestion.alumnos.updateDocente');
        Route::put('/update-laboral', [AlumnoController::class, 'updateTutorLaboral'])->name('gestion.alumnos.updateLaboral');

        // Matriculación
        Route::post('/matricular', [AlumnoController::class, 'matricular'])->name('gestion.alumnos.matricular');
        //Desmatricular (SoftDelete)
        Route::delete('/desmatricular/{modulo_id}', [AlumnoController::class, 'desmatricular'])
            ->name('gestion.alumnos.desmatricular');
        // Restaurar matrícula (Deshacer Soft Delete)
        Route::put('/restaurar-matricula/{modulo_id}', [AlumnoController::class, 'restaurarMatricula'])
            ->name('gestion.alumnos.restaurar');
    });

    // La ruta AJAX del desplegable de tutores no funciona correctamente si no está fuera del grupo de alumnos
    Route::get('/get-tutores-empresa/{empresa_id}', [AlumnoController::class, 'getTutoresPorEmpresa'])
        ->name('gestion.alumnos.getTutoresEmpresa');
    // La ruta AJAX del desplegable de periodos
    Route::post('/alumnos/update-periodo', [AlumnoController::class, 'updatePeriodo'])
        ->name('alumnos.update.periodo');
});