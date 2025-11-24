<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use Database\Seeders\AdminUserSeeder;
use Throwable;

class CrearGalileo extends Command
{
    /**
     * El nombre y la firma del comando de consola.
     * @var string
     */
    protected $signature = 'db:crear-galileo';

    /**
     * La descripción del comando de consola.
     * @var string
     */
    protected $description = 'Asegura la existencia y migra la base de datos principal de gestión (Galileo).';

    /**
     * Ejecuta el comando desde consola.
     */
    public function handle()
    {
        $dbName = config('database.connections.mysql.database');
        
        $this->info("Iniciando verificación y creación de la BD de gestión: '{$dbName}'...");

        // 1. Configurar y crear la base de datos si no existe
        if (!$this->crearBaseDeDatosSiNoExiste($dbName)) {
            return 1;
        }

        // 2. Ejecutar las migraciones en la BD principal
        if (!$this->ejecutarMigraciones($dbName)) {
            return 1;
        }

        $this->info("\nBase de datos '{$dbName}' creada y migrada con éxito.");
        return 0;
    }

    /**
     * Intenta crear la base de datos de gestión usando una conexión raw.
     */
    private function crearBaseDeDatosSiNoExiste(string $dbName): bool
    {
        // Configuramos una conexión "raw" sin seleccionar base de datos.
        $config = config('database.connections.mysql');
        $config['database'] = null;
        Config::set('database.connections.mysql_raw', $config);
        
        try {
            DB::connection('mysql_raw')->statement("CREATE DATABASE IF NOT EXISTS {$dbName}");//Crea Galileo
            $this->info("Base de datos '{$dbName}' asegurada (creada o ya existente).");
            return true;
        } catch (Throwable $e) {
            $this->error("Error fatal: No se pudo conectar/crear la BD '{$dbName}'.");
            $this->error("Asegúrate de que MySQL esté activo y las credenciales de 'mysql' sean correctas.");
            $this->error("Mensaje: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Ejecuta las migraciones en la base de datos de gestión.
     */
    private function ejecutarMigraciones(string $dbName): bool
    {
        $this->warn("Verificando migraciones en la BD '{$dbName}'...");

        try {
            // Comprobamos si la tabla de migraciones existe
            $tables = DB::connection('mysql')->select('SHOW TABLES LIKE "migrations"');

            if (empty($tables)) {
                $this->info("La tabla 'migrations' no existe. Ejecutando 'php artisan migrate'...");

                Artisan::call('migrate', [], $this->output);// Ejecutamos el comando migrate
                $this->info("Migraciones de gestión completadas con éxito.");
            } else {
                $this->info("La tabla 'migrations' ya existe. Las migraciones están al día.");
            }

            $this->call(AdminUserSeeder::class);// Añadimos el admin

            return true;

        } catch (Throwable $e) {
            $this->error("Error al ejecutar migraciones de gestión en '{$dbName}'.");
            $this->error("Mensaje: " . $e->getMessage());
            return false;
        }
    }
}