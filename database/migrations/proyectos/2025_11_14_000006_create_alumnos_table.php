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
            $table->uuid('tutor_laboral_id')->nullable()->index();//Aunque es una clave foranea, no se puede referenciar al estar en diferentes bases de datos, se deberá añadir en el modelo
            $table->uuid('tutor_docente_id')->nullable()->index();//Aunque es una clave foranea, no se puede referenciar al estar en diferentes bases de datos, se deberá añadir en el modelo
            
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
