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
        Schema::create('bases_de_datos', function (Blueprint $table) {
            $table->uuid('id_base_de_datos')->primary();
            $table->string('proyecto', 19)->unique();
            $table->string('conexion', 45)->unique();
            $table->boolean('finalizado');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bases_de_datos');
    }
};
