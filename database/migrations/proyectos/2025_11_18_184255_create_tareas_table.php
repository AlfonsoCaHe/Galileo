<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
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
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tareas');
    }
};
