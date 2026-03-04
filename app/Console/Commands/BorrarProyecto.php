<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BorrarProyecto extends Command
{
    // Nombre del comando y mensaje
    protected $signature = 'db:eliminar-proyecto {nombre_proyecto}';
    protected $description = 'Elimina físicamente la base de datos de un proyecto.';

    public function handle()
    {
        $nombreBD = $this->argument('nombre_proyecto');

        try {
            // 1. Ejecuta DROP DATABASE
            // Usamos la conexión por defecto (Galileo) para ejecutar la sentencia DDL.
            DB::connection(config('database.default'))->statement("DROP DATABASE IF EXISTS `{$nombreBD}`");

            $this->info("Base de datos '{$nombreBD}' eliminada físicamente con éxito.");
            Log::info("Base de datos '{$nombreBD}' eliminada físicamente por la aplicación web.");
            
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("ERROR: ERROR al eliminar la base de datos '{$nombreBD}': " . $e->getMessage());
            Log::error("Fallo al eliminar BD '{$nombreBD}'. Error: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}