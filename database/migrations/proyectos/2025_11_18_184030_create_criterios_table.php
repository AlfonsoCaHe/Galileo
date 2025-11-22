<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('criterios', function (Blueprint $table) {
            $table->uuid('id_criterio')->primary();
            $table->text('descripcion');
            $table->uuid('ras_id')->index(); 
            $table->timestamps();

            // Restricción de Clave Foránea
            $table->foreign('ras_id')
                  ->references('id_ras')
                  ->on('ras')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('criterios');
    }
};
