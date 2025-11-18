<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class PruebaTest extends Command
{
    /**
     * El nombre y la firma del comando de consola.
     * Añadimos 'db:test-prueba' para el entorno de pruebas.
     *
     * @var string
     */
    protected $signature = 'db:test-prueba';

    /**
     * La descripción del comando de consola.
     *
     * @var string
     */
    protected $description = 'Crea dos bases de datos de proyecto y ejecuta el Seeder de prueba para el entorno híbrido.';

    /**
     * Ejecuta el comando de consola.
     */
    public function handle()
    {
        $this->info("--- Iniciando Configuración de Entorno de Pruebas ---");

        // --- 1. Crear el Proyecto 2024 (proyecto_2024_2026) ---
        $this->comment("\n1. Creando Base de Datos para el proyecto 2024...");
        // Usamos $this->output para mostrar los mensajes de salida del comando hijo
        $exitCode2024 = Artisan::call('db:crear-proyecto', ['year_start' => 2024], $this->output);
        
        if ($exitCode2024 !== 0) {
            $this->error("Falló la creación del proyecto 2024. Deteniendo la prueba.");
            return 1;
        }

        // --- 2. Crear el Proyecto 2025 (proyecto_2025_2027) ---
        $this->comment("\n2. Creando Base de Datos para el proyecto 2025...");
        $exitCode2025 = Artisan::call('db:crear-proyecto', ['year_start' => 2025], $this->output);
        
        if ($exitCode2025 !== 0) {
            $this->error("Falló la creación del proyecto 2025. Deteniendo la prueba.");
            return 1;
        }

        // --- 3. Ejecutar el Seeder de prueba ---
        $this->comment("\n3. Ejecutando Seeder de Relaciones de Prueba (PruebaRelacionesSeeder)...");
        Artisan::call('db:seed', [
            '--class' => 'PruebaRelacionesSeeder',
            '--force' => true
        ], $this->output);
        
        $this->info("Seeder completado. El profesor, módulos, y alumnos han sido creados en las BDs correspondientes.");
        $this->info("\n--- Configuración de Pruebas Completa. Listo para probar ---");

        return 0;
    }
}