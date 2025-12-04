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
        Schema::create('cupos_empresas', function (Blueprint $table) {
            $table->uuid('id');
            
            $table->uuid('empresa_id'); 
            
            $table->string('periodo', 50);
            $table->integer('plazas')->default(0); // Cantidad de alumnos aceptados
            
            $table->timestamps();

            $table->foreign('empresa_id')
                ->references('id_empresa') // Apunta a la PK personalizada de tu tabla empresas
                ->on('empresas')
                ->onDelete('cascade'); // Si borras la empresa, se borran sus cupos
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cupos_empresas');
    }
};