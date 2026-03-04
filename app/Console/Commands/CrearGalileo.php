<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use Database\Seeders\AdminUserSeeder;
use App\Models\User;
use Throwable;

class CrearGalileo extends Command
{
    // Nombre del comando y mensaje
    protected $signature = 'db:crear-galileo';
    protected $description = 'Instala o actualiza la base de datos principal (Galileo) para el despliegue automático.';

    public function handle()
    {
        $dbName = config('database.connections.mysql.database');
        $this->info("INFO: Iniciando despliegue de la Base de Datos Principal: '{$dbName}'...");

        // 1. Creación física de la base de datos
        if (!$this->crearBaseDeDatos($dbName)) {
            return 1; // Error fatal
        }

        // 2. Migraciones (Estructura de las tablas)
        // Ejecutamos migraciones. Laravel gestiona internamente cuáles faltan.
        $this->info("AVISO: Verificando estructura de tablas (Migraciones)...");
        $exitCode = Artisan::call('migrate', ['--force' => true], $this->output);
        
        if ($exitCode !== 0) {
            $this->error("ERROR: Error al ejecutar las migraciones.");
            return 1;
        }

        // 3. Seeder Admin
        // Solo sembramos si no hay usuarios para evitar duplicar al admin en caso de despliegues sucesivos.
        if (User::count() === 0) {
            $this->info("INFO: Base de datos vacía. Creando usuario Administrador por defecto...");
            $this->call(AdminUserSeeder::class);
        } else {
            $this->comment("INFO: El usuario Administrador ya existe. Se omite la siembra.");
        }

        $this->info("INFO: Base de datos '{$dbName}' lista y operativa.");
        return 0;
    }

    /**
     * Conecta sin seleccionar BD y crea el esquema si no existe.
     */
    private function crearBaseDeDatos(string $dbName): bool
    {
        try {
            // Obtenemos la config actual pero quitamos el nombre de la BD para conectar al "root" de MySQL
            $config = config('database.connections.mysql');
            $originalDB = $config['database'];
            $config['database'] = null; 
            Config::set('database.connections.mysql_root', $config);

            // Consulta de creación segura
            DB::connection('mysql_root')->statement("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            
            return true;

        } catch (Throwable $e) {
            $this->error("ERROR: Error fatal al conectar/crear la BD '{$dbName}'.");
            $this->error("Comprueba: 1. MySQL está arrancado. 2. Usuario/Pass en .env son correctos.");
            $this->line("Detalle técnico: " . $e->getMessage());
            return false;
        }
    }
}