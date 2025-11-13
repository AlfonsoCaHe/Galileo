<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;

class CrearProyectoNuevoBD extends Command
{
    // Nombre del comando Artisan: db:crear-curso (fácil de usar)
    protected $signature = 'db:crear-curso {year_start?}';

    // Descripción
    protected $description = 'Crea y migra una nueva base de datos para un curso bianual (ej. 2026_2028) usando la plantilla de conexión.';

    public function handle()
    {
        // 1. Definir los años y nombres de la BD
        // El año de inicio se toma del argumento o del año actual si no se especifica
        $yearStart = $this->argument('year_start') ?? now()->year;
        $yearEnd = $yearStart + 2; // Para el curso bianual
        
        $newDbName = "Proyecto_{$yearStart}_{$yearEnd}";
        $connectionName = "course_{$yearStart}"; // Nombre dinámico de la nueva conexión

        // 2. Crear la base de datos físicamente en MySQL
        $this->info("Intentando crear la base de datos: {$newDbName}...");
        
        try {
            // Se usa la conexión principal (mysql) para ejecutar el CREATE DATABASE
            // Esto asume que el usuario 'root' de XAMPP tiene permisos para crear BDs.
            DB::statement("CREATE DATABASE IF NOT EXISTS {$newDbName}");
            $this->info("Base de datos '{$newDbName}' creada con éxito.");
        } catch (\Exception $e) {
            $this->error("Error al crear la BD. Asegúrate de que el usuario de MySQL ('root') tenga permisos y que XAMPP esté corriendo.");
            $this->error("Mensaje de error: " . $e->getMessage());
            return 1;
        }

        // 3. Configurar la conexión dinámica para las migraciones
        // Obtenemos la plantilla 'course_template' que definimos en database.php
        $newConfig = Config::get('database.connections.course_template');
        $newConfig['database'] = $newDbName;
        
        // Registramos la nueva conexión con un nombre único (ej: 'course_2024')
        Config::set("database.connections.{$connectionName}", $newConfig);
        
        // 4. Ejecutar las migraciones en la nueva BD
        $this->info("Ejecutando migraciones en la conexión '{$connectionName}'...");

        Artisan::call('migrate', [
            '--database' => $connectionName, // Usa la conexión dinámica recién registrada
            '--force' => true 
        ]);
        
        $this->info(Artisan::output());
        $this->info("¡Base de datos y tablas de {$newDbName} creadas y listas para usar!");
        return 0;
    }
}