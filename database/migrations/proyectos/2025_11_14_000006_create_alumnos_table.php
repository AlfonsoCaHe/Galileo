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
        Schema::create('alumnos', function (Blueprint $table) {
            $table->uuid('id_alumno')->primary();
            $table->string('nombre', 45);
            $table->uuid('tutor_laboral_id');
            $table->uuid('tutor_docente_id');

            //Claves foraneas
            $table->foreign('tutor_laboral_id')
                  ->references('id_tutor_laboral')       // Apunta al campo 'UUID' de la tabla empresas
                  ->on('tutores_laborales')         // De la tabla 'empresas'
                  ->onDelete('set null');    // Si se borra la empresa, el tutor laboral se desvincula
            
            $table->foreign('tutor_docente_id')
                  ->references('id_profesor')       // Apunta al campo 'UUID' de la tabla empresas
                  ->on('profesores')         // De la tabla 'empresas'
                  ->onDelete('set null');    // Si se borra la empresa, el tutor laboral se desvincula
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alumnos');
    }
};
