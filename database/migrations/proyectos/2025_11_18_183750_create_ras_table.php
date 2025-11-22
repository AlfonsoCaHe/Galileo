<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ras', function (Blueprint $table) {
            $table->uuid('id_ras')->primary();
            $table->string('nombre')->nullable()->index();
            $table->uuid('modulo_id')->index(); 
            $table->timestamps();

            // Restricción de Clave Foránea
            $table->foreign('modulo_id')
                  ->references('id_modulo')
                  ->on('modulos')
                  ->onDelete('cascade'); 
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ras');
    }
};