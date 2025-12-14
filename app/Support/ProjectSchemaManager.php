<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class ProjectSchemaManager
{
    /**
     * Define y crea todas las tablas necesarias para la base de datos de un proyecto.
     * * @param string $connectionName El nombre de la conexión dinámica (ej. 'proyecto_2025_2027').
     * @param string $proyectoId El UUID del proyecto de la BD principal (Galileo).
     * @return void
     */
    public static function createAllTables(string $connectionName, string $proyectoId): void
    {
        $schema = Schema::connection($connectionName);// Usamos Schema::connection() para dirigir las operaciones al proyecto dinámico.

        // 1. Tabla: modulos
        $schema->create('modulos', function (Blueprint $table) use ($proyectoId) {
            $table->uuid('id_modulo')->primary();
            $table->string('nombre');
            $table->uuid('proyecto_id')->default($proyectoId); // FK a la BD principal (Galileo)
            $table->timestamps();
        });

        // 2. Tabla: alumnos
        $schema->create('alumnos', function (Blueprint $table) {
            $table->uuid('id_alumno')->primary();
            $table->string('nombre');
            $table->enum('periodo', ['Periodo 1', 'Periodo 2']);// Periodo de empresa del alumno
            $table->uuid('tutor_laboral_id')->nullable(); // FK a la BD principal
            $table->uuid('tutor_docente_id')->nullable(); // FK a la BD principal
            $table->timestamps();
        });

        // 3. Tablas de RAs, Criterios, Tareas y Actividades
        $schema->create('ras', function (Blueprint $table) {
            $table->uuid('id_ras')->primary();
            $table->uuid('modulo_id'); // FK a 'modulos'
            $table->string('codigo');
            $table->string('descripcion');
            $table->timestamps();
            
            $table->foreign('modulo_id')->references('id_modulo')->on('modulos')->onDelete('cascade');
        });

        $schema->create('criterios', function (Blueprint $table) {
            $table->uuid('id_criterio')->primary();
            $table->uuid('ras_id'); // FK a 'ras'
            $table->string('ce', 10); 
            $table->text('descripcion');
            $table->timestamps();

            $table->foreign('ras_id')->references('id_ras')->on('ras')->onDelete('cascade');
        });

        $schema->create('actividades', function (Blueprint $table) {
            $table->uuid('id_actividad')->primary();
            $table->string('nombre');
            $table->text('tarea')->unique(); // Texto que contendrá el desplegable, no se puede repetir
            $table->text('descripcion')->nullable();
            $table->uuid('modulo_id');
            $table->timestamps();

            $table->foreign('modulo_id')->references('id_modulo')->on('modulos')->onDelete('cascade');
        });

        $schema->create('tareas', function (Blueprint $table) {
            $table->uuid('id_tarea')->primary();
            
            $table->uuid('actividad_id');
            $table->uuid('alumno_id');
            $table->uuid('modulo_id');
            
            $table->string('tarea');
            $table->text('notas_alumno')->nullable();
            $table->time('duracion')->nullable();
            $table->date('fecha')->nullable();
            $table->boolean('apto')->default(false);
            $table->boolean('bloqueado')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('actividad_id')->references('id_actividad')->on('actividades')->onDelete('cascade');
            $table->foreign('alumno_id')->references('id_alumno')->on('alumnos')->onDelete('cascade');
            $table->foreign('modulo_id')->references('id_modulo')->on('modulos')->onDelete('cascade');
        });

        // 4. Tablas Pivote (Many-to-Many)

        // Relación Alumno <--> Módulo
        $schema->create('alumno_modulo', function (Blueprint $table) {
            $table->uuid('alumno_id');
            $table->uuid('modulo_id');
            $table->primary(['alumno_id', 'modulo_id']);

            $table->timestamps();
            $table->softDeletes();
            $table->unique(['alumno_id', 'modulo_id', 'deleted_at'], 'alumno_modulo_unique');//Evitamos que si un alumno se ha vuelto a matricular se pueda recuperar el registro

            $table->foreign('alumno_id')->references('id_alumno')->on('alumnos')->onDelete('cascade');
            $table->foreign('modulo_id')->references('id_modulo')->on('modulos')->onDelete('cascade');
        });

        // Relación Profesor <--> Módulo
        // NOTA: 'profesor_id' es FK a la BD principal, no hay FKs foráneas de las bases dinámicas.
        $schema->create('profesor_modulo', function (Blueprint $table) {
            $table->uuid('profesor_id');
            $table->uuid('modulo_id');
            $table->primary(['profesor_id', 'modulo_id']);

            $table->foreign('modulo_id')->references('id_modulo')->on('modulos')->onDelete('cascade');
        });

        //Relación Actividad <--> Criterio
        $schema->create('actividad_criterio', function (Blueprint $table) {
            $table->uuid('actividad_id');
            $table->uuid('criterio_id');
            $table->primary(['actividad_id', 'criterio_id']);

            $table->foreign('actividad_id')->references('id_actividad')->on('actividades')->onDelete('cascade');
            $table->foreign('criterio_id')->references('id_criterio')->on('criterios')->onDelete('cascade');
        });
    }
    
    /**
     * Método auxiliar para el Seeder de pruebas (opcional)
     */
    public static function dropAllTables(array $tables, string $connectionName): void
    {
         $schema = Schema::connection($connectionName);
         foreach ($tables as $table) {
             $schema->dropIfExists($table);
         }
    }
}