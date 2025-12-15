<?php

use App\Http\Controllers\ProfesoradoDocenteController;
use App\Http\Controllers\AlumnadoVistaController;
use App\Http\Controllers\TutoresLaboralesVistaController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ProfesorCheck;
use App\Http\Middleware\AlumnoCheck;
use App\Http\Middleware\SetProjectConnection;
use App\Http\Middleware\TutorLaboralCheck;

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

    //----------------------------------Rutas alumnado----------------------------------------------------//
    Route::middleware([AlumnoCheck::class])->group(function () {
        Route::get('/alumnos/panel', [AlumnadoVistaController::class, 'index'])
            ->name('alumnos.panel'); 

        // Ruta del alumnado para redirigir a la vista de tareas finalizadas
        Route::get('/alumnado/{proyecto_id}/tareas_finalizadas', [AlumnadoVistaController::class, 'tareasRealizadas'])
            ->name('alumnado.tareas_realizadas');

        // Ruta del alumnado para redirigir a la vista de tareas pendientes
        Route::get('/alumnado/{proyecto_id}/tareas_pendientes', [AlumnadoVistaController::class, 'tareasPendientes'])
            ->name('alumnado.tareas_pendientes');
        
        //Rutas del alumnado para el CRUD de tareas
        Route::get('/tareas/{proyecto_id}/crear', [AlumnadoVistaController::class, 'crearTarea'])
            ->name('alumnado.createTarea');
        Route::post('/{proyecto_id}/store-tarea', [AlumnadoVistaController::class, 'storeTarea'])
            ->name('alumnado.storeTarea');
        Route::get('tareas/{proyecto_id}/{modulo_id}/{tarea_id}/edit', [AlumnadoVistaController::class, 'editTarea'])
            ->name('alumnado.editTarea');
        Route::put('/{proyecto_id}/actualizar-tarea/{tarea_id}', [AlumnadoVistaController::class, 'updateTarea'])
            ->name('alumnado.updateTarea');
        Route::delete('/{proyecto_id}/eliminar-tarea/{tarea_id}', [AlumnadoVistaController::class, 'destroyTarea'])
            ->name('alumnado.destroyTarea');

        //Rutas del alumnado para modificar sus datos
        Route::get('/alumno/{proyecto_id}/editar', [AlumnadoVistaController::class, 'editar'])
            ->name('alumno.editar');
        Route::put('/alumno/{proyecto_id}/{alumno_id}/update', [AlumnadoVistaController::class, 'update'])
            ->name('alumno.update');
    });

    //------------------------------Rutas tutores laborales-----------------------------------------------//
    Route::middleware([TutorLaboralCheck::class])->group(function () {

        //Ruta para el listado de alumnos tutorizados
        Route::get('/tutor_laboral/tutorizados', [TutoresLaboralesVistaController::class, 'tutorizados'])
            ->name('tutores_laborales.panel');

        // Ver tareas de un alumno específico (necesita proyecto_id y alumno_id)
        Route::get('/tutor_laboral/proyecto/{proyecto_id}/alumno/{alumno_id}/tareas', [TutoresLaboralesVistaController::class, 'tareasAlumnoTutorizado'])
            ->name('tutores_laborales.tareas.alumno');

        // Editar perfil/datos del tutor laboral
        Route::get('/tutor_laboral/editar/{tutor_laboral_id}', [TutoresLaboralesVistaController::class, 'editar'])
            ->name('tutores_laborales.editar');

        // Actualizar datos/contraseña del tutor laboral
        Route::put('/tutor_laboral/update/{tutor_laboral_id}', [TutoresLaboralesVistaController::class, 'update'])
            ->name('tutores_laborales.update');

    });
});