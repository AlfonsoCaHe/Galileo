<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Artisan;
use App\Models\Proyecto;
use App\Support\ProjectSchemaManager;
use Throwable;

class CrearProyectoNuevoBD extends Command
{
    protected $signature = 'db:crear-proyecto {year_start?}';
    protected $description = 'Crea una nueva base de datos dinámica para un proyecto escolar.';

    public function handle()
    {
        // 1. CALCULAR NOMBRE DEL PROYECTO
        $yearStart = $this->argument('year_start') ?? now()->year;
        
        if (!is_numeric($yearStart) || strlen($yearStart) != 4) {
            $this->error("❌ El año debe ser un número de 4 dígitos.");
            return 1;
        }

        $yearEnd = (int)$yearStart + 2; // Ciclos de 2 años
        $dbName = "proyecto_{$yearStart}_{$yearEnd}";
        $nombreProyecto = "proyecto_{$yearStart}_{$yearEnd}";

        $this->info("🚀 Iniciando creación del proyecto: '{$nombreProyecto}' (BD: {$dbName})...");

        // 2. CREAR LA BASE DE DATOS FÍSICA
        // Usamos una conexión temporal sin seleccionar BD
        if (!$this->crearBaseDeDatosFisica($dbName)) {
            return 1;
        }

        // 3. REGISTRAR EN LA TABLA 'PROYECTOS' (BD GALILEO)
        // Esto asume que Galileo ya existe. Si falla aquí, es que Galileo no está instalado.
        try {
            $proyecto = Proyecto::firstOrCreate(
                ['conexion' => $dbName], // Buscamos por nombre de conexión única
                [
                    'proyecto' => $nombreProyecto,
                    'finalizado' => false
                ]
            );
            $this->comment("📋 Proyecto registrado en Galileo (ID: {$proyecto->id_base_de_datos})");

        } catch (Throwable $e) {
            $this->error("❌ Error: No se pudo registrar el proyecto en Galileo.");
            $this->error("¿Has ejecutado 'php artisan db:crear-galileo' primero?");
            return 1;
        }

        // 4. CREAR LAS TABLAS (SCHEMA) DENTRO DEL PROYECTO
        if ($this->crearEsquemaTablas($dbName, $proyecto->id_base_de_datos)) {
            $this->info("✨ ¡Proyecto '{$nombreProyecto}' desplegado correctamente!");
            return 0;
        }

        return 1;
    }

    private function crearBaseDeDatosFisica(string $dbName): bool
    {
        try {
            $config = config('database.connections.mysql');
            $config['database'] = null; // Conectar sin BD específica
            Config::set('database.connections.mysql_temp_creation', $config);

            DB::connection('mysql_temp_creation')->statement("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;");
            return true;
        } catch (Throwable $e) {
            $this->error("❌ Error al crear la BD física '{$dbName}': " . $e->getMessage());
            return false;
        }
    }

    private function crearEsquemaTablas(string $dbName, string $proyectoId): bool
    {
        // Configuramos la conexión dinámica para usar el Schema Manager
        $connectionName = 'dynamic_setup_' . $dbName;
        $config = config('database.connections.mysql');
        $config['database'] = $dbName;
        Config::set("database.connections.{$connectionName}", $config);

        try {
            // Llamamos a tu clase de soporte para crear tablas
            ProjectSchemaManager::createAllTables($connectionName, $proyectoId);
            return true;
        } catch (Throwable $e) {
            $this->error("❌ Error creando tablas en '{$dbName}'.");
            $this->line($e->getMessage());
            
            // Opcional: Eliminar la BD si falla el esquema para no dejar basura
            // DB::connection('mysql_temp_creation')->statement("DROP DATABASE IF EXISTS `{$dbName}`");
            return false;
        } finally {
            // Limpieza
            DB::purge($connectionName);
        }
    }
}