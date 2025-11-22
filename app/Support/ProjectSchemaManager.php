<?php

namespace App\Support;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ProjectSchemaManager
{
    /**
     * Define la tabla 'modulos'.
     */
    public static function createModulosTable(): void
    {
        Schema::create('modulos', function (Blueprint $table) {
            $table->uuid('id_modulo')->primary();
            $table->string('nombre', 70);
            $table->timestamps();
        });
    }
    
    /**
     * Define la tabla 'alumnos'.
     */
    public static function createAlumnosTable(): void
    {
        Schema::create('alumnos', function (Blueprint $table) {
            $table->uuid('id_alumno')->primary();
            $table->string('nombre', 45);
            $table->uuid('tutor_laboral_id')->nullable()->index();
            $table->uuid('tutor_docente_id')->nullable()->index();
            $table->timestamps();
        });
    }
    
    /**
     * Define la tabla pivote 'profesor_modulo'.
     */
    public static function createProfesorModuloTable(): void
    {
        Schema::create('profesor_modulo', function (Blueprint $table) {
            $table->uuid('profesor_id'); 
            $table->uuid('modulo_id'); 

            $table->primary(['profesor_id', 'modulo_id']); 

            // Clave foránea local:
            $table->foreign('modulo_id')
                ->references('id_modulo')
                ->on('modulos')
                ->onDelete('cascade'); 
        });
    }

    /**
     * Define la tabla pivote 'alumnos_modulos'.
     */
    public static function createAlumnosModuloTable(): void
    {
        Schema::create('alumnos_modulos', function (Blueprint $table) {
            $table->uuid('alumno_id');
            $table->uuid('modulo_id');

            $table->primary(['alumno_id', 'modulo_id']);

            //Claves foraneas
            $table->foreign('alumno_id')
                  ->references('id_alumno')       // Apunta al campo 'UUID' de la tabla tutores_laborales
                  ->on('alumnos')         // De la tabla 'empresas'
                  ->onDelete('cascade');    // Si se borra el tutor laboral, el tutor laboral se desvincula
            
            $table->foreign('modulo_id')
                  ->references('id_modulo')       // Apunta al campo 'UUID' de la tabla profesores
                  ->on('modulos')         // De la tabla 'empresas'
                  ->onDelete('cascade');    // Si se borra el profesor, el tutor docente se desvincula
        });
    }

    /**
     * Define la tabla 'ras'.
     */
    public static function createRasTable(): void
    {
        Schema::create('ras', function (Blueprint $table) {
            $table->uuid('id_ras')->primary();
            $table->string('nombre')->nullable()->index();
            
            $table->uuid('modulo_id')->index(); 
            $table->foreign('modulo_id')
                ->references('id_modulo')
                ->on('modulos')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    /**
     * Define la tabla 'criterios'.
     */
    public static function createCriteriosTable(): void
    {
        Schema::create('criterios', function (Blueprint $table) {
            $table->uuid('id_criterio')->primary();
            $table->text('descripcion');
            $table->uuid('ras_id')->index(); 
            $table->timestamps();

            // Restricción de Clave Foránea
            $table->foreign('ras_id')
                  ->references('id_ras')
                  ->on('ras')
                  ->onDelete('cascade');
        });
    }

    /**
     * Define la tabla 'tareas'.
     */
    public static function createTareasTable(): void
    {
        Schema::create('tareas', function (Blueprint $table) {
            $table->uuid('id_tarea')->primary();
            $table->text('actividad');
            $table->uuid('modulo_id')->nullable()->index();
            $table->uuid('alumno_id')->nullable()->index();
            $table->boolean('apto')->default(false);

            //Claves foraneas
            $table->foreign('modulo_id')
                  ->references('id_modulo')// Apunta al campo 'UUID' de la tabla modulos
                  ->on('modulos')// De la tabla 'modulos'
                  ->onDelete('set null');// Si se borra el módulo, la tarea se desvincula

            $table->foreign('alumno_id')
                  ->references('id_alumno')// Apunta al campo 'UUID' de la tabla alumnos
                  ->on('alumnos')// De la tabla 'alumnos'
                  ->onDelete('set null');// Si se borra el alumno, la tarea se desvincula
            $table->timestamps();
            });
    }

    /**
     * Define la tabla pivote 'tareas_criterios'.
     */
    public static function createTareasCriteriosTable(): void
    {
        Schema::create('tareas_criterios', function (Blueprint $table) { 
            $table->uuid('tarea_id');
            $table->uuid('criterio_id');

            $table->primary(['tarea_id', 'criterio_id']);
            
            // Claves foráneas a tareas y criterios
            $table->foreign('tarea_id')
                ->references('id_tarea')
                ->on('tareas')
                ->onDelete('cascade');
                
            $table->foreign('criterio_id')
                ->references('id_criterio')
                ->on('criterios')
                ->onDelete('cascade');
        });
    }
    
    public static function dropAllTables(array $tables): void
    {
        foreach ($tables as $table) {
            Schema::dropIfExists($table);
        }
    }
}