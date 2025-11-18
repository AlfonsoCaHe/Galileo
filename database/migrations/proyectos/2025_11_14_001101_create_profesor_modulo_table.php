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
        Schema::create('profesor_modulo', function (Blueprint $table) {
            $table->uuid('profesor_id'); //No se puede referenciar al ser de bases de datos diferentes, se deberá hacer en el modelo
            $table->uuid('modulo_id'); 

            $table->primary(['profesor_id', 'modulo_id']); 

            //Claves foraneas
            
            $table->foreign('modulo_id')
                ->references('id_modulo')
                ->on('modulos')
                ->onDelete('cascade'); 
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profesor_modulo');
    }
};
