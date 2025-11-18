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
        Schema::create('criterios', function (Blueprint $table) {
            $table->uuid('id_criterio')->primary();
            $table->string('nombre', 70);
            $table->string('descripcion', 255);
            $table->uuid('ras_id')->nullable()->index();
            //Clave foranea
            $table->foreign('ras_id')
                  ->references('id_ras')// Apunta al campo 'UUID' de la tabla ras
                  ->on('ras')// De la tabla 'ras'
                  ->onDelete('set null');// Si se borra el ra, el criterio se desvincula
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('criterios');
    }
};
