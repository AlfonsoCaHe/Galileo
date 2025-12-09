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
            $table->string('periodo')->nullable();// Periodo de empresa del alumno
            $table->uuid('tutor_laboral_id')->nullable(); // FK a la BD principal
            $table->uuid('tutor_docente_id')->nullable(); // FK a la BD principal
            $table->timestamps();
        });

        // 3. Tablas de RAs, Criterios y Tareas
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

        $schema->create('tareas', function (Blueprint $table) {
            $table->uuid('id_tarea')->primary();
            
            //Parte del profesor (Profesor)
            $table->string('nombre');// Código de Actividad (Ej: MM01)
            $table->string('tarea')->nullable();// Desplegable con el nombre que verá el alumno
            $table->text('descripcion')->nullable(); // Instrucciones
            $table->boolean('bloqueado')->default(false);//Evita que se pueda modificar la tarea una vez es true
            
            //Parte del Alumno
            $table->text('notas_alumno')->nullable();// Anotaciones del alumno
            $table->date('fecha')->nullable();//Con un calendario
            $table->string('duracion', 5)->nullable(); // HH:MM (Ej: 2:30)
            
            $table->uuid('modulo_id');
            $table->uuid('alumno_id');
            
            //Parte del tutor laboral
            $table->boolean('apto')->default(false);

            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('modulo_id')->references('id_modulo')->on('modulos')->onDelete('cascade');
            $table->foreign('alumno_id')->references('id_alumno')->on('alumnos')->onDelete('cascade');       
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
        // NOTA: 'profesor_id' es FK a la BD principal, no hay FK foránea en la base dinámica.
        $schema->create('profesor_modulo', function (Blueprint $table) {
            $table->uuid('profesor_id');
            $table->uuid('modulo_id');
            $table->primary(['profesor_id', 'modulo_id']);

            $table->foreign('modulo_id')->references('id_modulo')->on('modulos')->onDelete('cascade');
        });

        // Relación Tarea <--> Criterio
        $schema->create('tareas_criterios', function (Blueprint $table) {
            $table->uuid('tarea_id');
            $table->uuid('criterio_id');
            $table->primary(['tarea_id', 'criterio_id']);
            
            $table->foreign('tarea_id')->references('id_tarea')->on('tareas')->onDelete('cascade');
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