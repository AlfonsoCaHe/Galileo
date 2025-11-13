<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Throwable; 

class CrearProyectoNuevoBD extends Command
{
    protected $signature = 'db:crear-proyecto {year_start?}';

    protected $description = 'Crea una nueva base de datos para un proyecto bianual (ej. proyecto_2026_2028). En caso de no introducir un año inicial como argumento, se generará con el año actual.';

    public function handle()
    {
        // 1. VERIFICACIÓN DE LA BD PRINCIPAL (en nuestro caso galileo)
        if (!$this->comprobarBaseDeDatosGestion()) {
            return 1;
        }

        // 2. Definimos los años y el nombre de la BD
        $yearStart = $this->argument('year_start') ?? now()->year;
        $yearEnd = $yearStart + 2; 
        
        $newDbName = "proyecto_{$yearStart}_{$yearEnd}";
        $connectionName = "proyecto_{$yearStart}"; 

        // 3. Crea la base de datos físicamente en MySQL
        $this->info("Intentando crear la base de datos: {$newDbName}...");
        
        try {
            DB::connection('mysql')->statement("CREATE DATABASE IF NOT EXISTS {$newDbName}");
            $this->info("Base de datos '{$newDbName}' creada con éxito.");
        } catch (\Exception $e) {
            $this->error("Error al crear la BD del proyecto. Mensaje: " . $e->getMessage());
            return 1;
        }

        // 4. Registra el proyecto en la tabla de gestión 'Bases_de_Datos'
        try {
            DB::connection('mysql')->table('bases_de_datos')->insert([
                'id' => (string) Str::uuid(),
                'proyecto' => $newDbName,
                'conexion' => $connectionName,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $this->info("Registro de la BD añadido a la tabla 'Bases_de_Datos' con éxito.");
        } catch (\Exception $e) {
            $this->error("ERROR: No se pudo insertar el registro en 'Bases_de_Datos'. Mensaje: " . $e->getMessage());
            return 1;
        }

        // 5. Configura la conexión dinámicamente para las migraciones
        $newConfig = Config::get('database.connections.course_template'); 
        $newConfig['database'] = $newDbName;
        
        Config::set("database.connections.{$connectionName}", $newConfig);
        
        // 6. Ejecuta las migraciones en la nueva BD
        $this->info("Ejecutando solo las migraciones específicas del proyecto en '{$connectionName}'...");

        Artisan::call('migrate', [
            '--database' => $connectionName, 
            '--path' => 'database/migrations/proyectos', 
            '--force' => true 
        ]);
        
        $this->info(Artisan::output());
        $this->info("Base de datos y tablas de {$newDbName} creadas. Proceso finalizado");
        return 0;
    }

    /**
     * Asegura que la BD principal (en nuestro caso galileo) esté lista.
     * Si la BD no existe, la crea. Si no está migrada, la migra.
     * Retorna true si está lista, false en caso de error.
     */
    protected function comprobarBaseDeDatosGestion()
    {
        $dbName = config('database.connections.mysql.database');
        
        try {
            // Intentar una consulta para verificar la conexión y existencia de la BD
            DB::connection('mysql')->select('SELECT 1');
            $this->info("Conexión con la BD '{$dbName}' correcta.");

        } catch (Throwable $e) {
            // Error al intentar conectar. Podría ser porque la BD no existe.
            $this->warn("La base de datos principal '{$dbName}' no existe o hay un error de conexión.");

            // 1. Intentamos crear la BD, cambiando temporalmente la conexión a 'mysql' sin BD específica
            $config = config('database.connections.mysql');
            $config['database'] = null; // Quitar el nombre de la BD para conectar al servidor
            Config::set('database.connections.mysql_raw', $config);
            
            try {
                DB::connection('mysql_raw')->statement("CREATE DATABASE IF NOT EXISTS {$dbName}");
                $this->info("Base de datos '{$dbName}' creada con éxito. (Preparando entorno de despliegue)");
            } catch (Throwable $e2) {
                // Fallo al crear la BD (permisos o MySQL no está corriendo)
                $this->error("Error fatal: No se pudo conectar/crear la BD '{$dbName}'.");
                $this->error("Asegúrate de que MySQL esté activo. Mensaje: " . $e2->getMessage());
                return false;
            }
        }

        // 2. Verificar y ejecutar las migraciones de gestión (siempre usando la conexión 'mysql')
        try {
            $tables = DB::connection('mysql')->select('SHOW TABLES LIKE "migrations"');//consulta SQL nativa para comprobar si hay tablas que se llamen 'migraciones'

            if (empty($tables)) {
                $this->warn("La tabla de migraciones no existe. Ejecutando 'php artisan migrate' en BD principal...");

                // Ejecutamos el comando migrate para crear todas las tablas de gestión.
                Artisan::call('migrate', [], $this->output);
                
                $this->info("Migraciones de gestión completadas con éxito.");
            }
            
            return true;
        } catch (Throwable $e3) {
            $this->error("Error al ejecutar migraciones de gestión. Mensaje: " . $e3->getMessage());
            return false;
        }
    }
}