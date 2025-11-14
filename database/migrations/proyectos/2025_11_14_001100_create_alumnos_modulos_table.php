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
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alumnos_modulos');
    }
};
