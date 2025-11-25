<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        // 1. TABLA USERS POR DEFECTO DE LARAVEL: Modificada para usar UUIDs y Roles Polimórficos
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary(); 
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            
            // Campo de enum 'rol' para la verificación rápida de perfiles
            $table->enum('rol', ['admin', 'alumno', 'profesor', 'tutor_laboral']); 

            $table->uuid('rolable_id')->nullable();
            $table->string('rolable_type')->nullable();
            
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. TABLA password_reset_tokens: Esencial para la funcionalidad de "Olvidé mi contraseña"
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        // 3. TABLA sessions: Necesaria para el almacenamiento de sesiones
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->uuid('user_id')->nullable()->index(); 
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};