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
        Schema::create('tutores_laborales', function (Blueprint $table) {
            $table->uuid('id_tutor_laboral')->primary();
            $table->string('nombre', 70);
            $table->string('email', 70);
            $table->uuid('empresa_id')->nullable(); 
            
            // Definición de la clave foránea
            $table->foreign('empresa_id')
                  ->references('id_empresa')// Apunta al campo 'UUID' de la tabla empresas
                  ->on('empresas')// De la tabla 'empresas'
                  ->onDelete('set null');// Si se borra la empresa, el tutor laboral se desvincula
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tutores_laborales');
    }
};
