<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class PruebaTest extends Command
{
    // Cambiamos el nombre para que sea más claro para el usuario final
    protected $signature = 'app:instalar'; 
    protected $description = 'Instalación automática completa de Galileo y proyectos iniciales.';

    public function handle()
    {
        $this->info("📦 INICIANDO INSTALACIÓN AUTOMÁTICA DE GALILEO");
        $this->info("---------------------------------------------");

        // PASO 1: Instalar BD Principal (Galileo)
        // Si ya existe, la verificará y actualizará sin romper nada.
        $this->comment("1️⃣  Configurando Sistema Central...");
        if (Artisan::call('db:crear-galileo', [], $this->output) !== 0) {
            $this->error("STOP: Falló la instalación de Galileo.");
            return 1;
        }

        // PASO 2: Crear Proyecto Actual (Ej: 2024)
        $year = now()->year;
        $this->comment("\n2️⃣  Creando Proyecto del año actual ({$year})...");
        Artisan::call('db:crear-proyecto', ['year_start' => $year], $this->output);

        // PASO 3: (Opcional) Datos de prueba
        // Preguntar al usuario si quiere datos de prueba (interactivo)
        if ($this->confirm('¿Deseas poblar la base de datos con información de prueba (Seeders)?', true)) {
             $this->comment("\n3️⃣  Generando datos de prueba...");
             Artisan::call('db:seed', [
                '--class' => 'PruebaRelacionesSeeder', 
                '--force' => true
             ], $this->output);
        }

        $this->info("\n✅ INSTALACIÓN COMPLETADA EXITOSAMENTE.");
        $this->info("   - Usuario Admin: admin@galileo.com");
        $this->info("   - Contraseña:  password");
        
        return 0;
    }
}