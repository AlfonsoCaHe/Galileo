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
        Schema::create('tareas_criterios', function (Blueprint $table) {
            $table->uuid('tarea_id');
            $table->uuid('criterio_id');

            $table->primary(['tarea_id', 'criterio_id']);
            //Claves foraneas
            $table->foreign('tarea_id')
                  ->references('id_tarea')// Apunta al campo 'UUID' de la tabla tareas
                  ->on('tareas')// De la tabla 'tareas'
                  ->onDelete('cascade');// Si se borra la tarea, desaparecen sus relaciones

            $table->foreign('criterio_id')
                  ->references('id_criterio')// Apunta al campo 'UUID' de la tabla criterios
                  ->on('criterios')// De la tabla 'criterios'
                  ->onDelete('cascade');// Si se borra el criterio, desaparecen sus relaciones
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tareas_criterios');
    }
};
