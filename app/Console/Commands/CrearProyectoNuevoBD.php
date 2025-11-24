<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use App\Models\Proyecto;
use App\Support\ProjectSchemaManager;

use Throwable;

class CrearProyectoNuevoBD extends Command
{
    /**
     * El nombre y la firma del comando de consola.
     * Añadimos 'db:crear-proyecto' para crear bases de datos dinámicamente.
     *
     * @var string
     */
    protected $signature = 'db:crear-proyecto {year_start?}';

    /**
     * La descripción del comando de consola.
     *
     * @var string
     */
    protected $description = 'Crea una nueva base de datos para un proyecto bianual (ej. proyecto_2026_2028). En caso de no introducir un año inicial como argumento, se generará con el año actual.';

    /**
     * Ejecuta el comando desde consola.
     */
    public function handle()
    {
        // 1. LLAMAR AL COMANDO DE GESTIÓN
        // Esto asegura que la BD principal (Galileo) exista, esté migrada y tenga el Admin User.
        $this->info("Verificando que la BD de gestión (Galileo) esté lista...");
        
        // Ejecutamos el nuevo comando db:crear-galileo
        $result = Artisan::call('db:crear-galileo', [], $this->output);

        if ($result !== 0) {
            $this->error("La BD de gestión (Galileo) no está lista. Abortando la creación del proyecto.");
            return 1;
        }

        // 2. Definimos los años, el nombre de la BD y de la conexión
        $yearStart = $this->argument('year_start') ?? now()->year;
        $yearEnd = $yearStart + 2;
        $newDbName = "proyecto_{$yearStart}_{$yearEnd}";
        $connectionName = "proyecto_{$yearStart}_{$yearEnd}_conn"; // Nombre de conexión temporal

        $this->info("Preparando la creación del proyecto bianual: {$newDbName}");
        
        // 3. Crear el registro en la tabla de Proyectos (BD Galileo)
        $proyecto = Proyecto::firstOrCreate(
            ['proyecto' => $newDbName], // Buscamos por nombre
            [
                'conexion' => $newDbName,
                'year_start' => $yearStart,
                'year_end' => $yearEnd,
                'finalizado' => false,
                'id_base_de_datos' => (string) Str::uuid()
            ]
        );

        if ($proyecto->wasRecentlyCreated) {
            $this->info("Registro de proyecto creado en Galileo con ID: {$proyecto->id_base_de_datos}");
        } else {
            $this->warn("El proyecto '{$newDbName}' ya existía. Continuaremos usando el registro existente.");
        }

        // 4. Crear la Base de Datos física para el proyecto
        if (!$this->crearBaseDeDatosProyecto($newDbName)) {
            return 1;
        }

        // 5. Crear el Tablas del Proyecto con Schema
        if (!$this->crearEsquemaProyecto($newDbName, $connectionName, $proyecto->id_base_de_datos)) {
            return 1;
        }
        
        $this->info("\nProyecto {$newDbName} creado");
        return 0;
    }

    /**
     * Crea la base de datos física para el proyecto si no existe.
     */
    private function crearBaseDeDatosProyecto(string $dbName): bool
    {
        // Usamos la conexión "raw" para crear la BD sin seleccionarla.
        $config = config('database.connections.mysql');
        $config['database'] = null;
        Config::set('database.connections.mysql_raw_proj', $config);
        
        try {
            DB::connection('mysql_raw_proj')->statement("CREATE DATABASE IF NOT EXISTS {$dbName}");
            $this->info("Base de datos física '{$dbName}' asegurada (creada o ya existente).");
            return true;
        } catch (Throwable $e) {
            $this->error("Error fatal: No se pudo crear la BD '{$dbName}' para el proyecto.");
            $this->error("Mensaje: " . $e->getMessage());
            return false;
        } finally {
            // Limpieza de la conexión temporal
            DB::purge('mysql_raw_proj');
            Config::offsetUnset('database.connections.mysql_raw_proj');
        }
    }

    /**
     * Configura la conexión dinámica y crea el esquema de tablas del proyecto.
     */
    private function crearEsquemaProyecto(string $dbName, string $connectionName, string $proyectoId): bool
    {
        $this->warn("Creando esquema (tablas) para el proyecto '{$dbName}'...");
        
        // 1. Configurar la conexión dinámica
        $baseConfig = config('database.connections.mysql');
        $newConfig = $baseConfig;
        $newConfig['database'] = $dbName;
        Config::set("database.connections.{$connectionName}", $newConfig);
        
        try {
            // 2. Ejecutar la lógica de creación de tablas usando el Manager
            ProjectSchemaManager::createAllTables($connectionName, $proyectoId);
            
            $this->info("Esquema de tablas creado con éxito en '{$dbName}'.");
            return true;
        } catch (Throwable $e) {
            $this->error("Error al crear el esquema de tablas en '{$dbName}'.");
            $this->error("Mensaje: " . $e->getMessage());
            
            // Intentar eliminar la BD si falla el esquema
            $this->eliminarBaseDeDatos($dbName); 
            
            return false;
        } finally {
            // 3. Limpieza de la conexión temporal
            DB::purge($connectionName);
            Config::offsetUnset("database.connections.{$connectionName}");
        }
    }

    /**
     * Método auxiliar para eliminar la BD en caso de fallo (para limpieza).
     */
    private function eliminarBaseDeDatos(string $dbName): void
    {
        try {
            DB::connection('mysql_raw_proj')->statement("DROP DATABASE IF EXISTS {$dbName}");
            $this->warn("Se eliminó la BD '{$dbName}' debido a un fallo en la creación del esquema.");
        } catch (Throwable $e) {
            $this->error("No se pudo limpiar la BD '{$dbName}' tras el fallo.");
        }
    }
}