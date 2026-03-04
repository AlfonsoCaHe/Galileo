<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Proyecto;
use App\Models\Profesor;
use App\Models\Empresa;
use App\Models\TutorLaboral;
use App\Models\User;

/**
 * SOLO SE UTILIZA EN ENTORNO DE PRUEBAS. SE EMPLEA EN REINICIOS DE LA BASE DE DATOS. EL BORRADO ES FÍSICO.
 */
class LimpiarTest extends Command
{
    /**
     * Nombre del comando.
     */
    protected $signature = 'db:borrar-test';

    /**
     * Descripción del comando que aparece por consola.
     */
    protected $description = 'Limpia el entorno de pruebas: elimina físicamente las BDs de proyecto y vacía tablas específicas en Galileo.';

    /**
     * Ejecuta el comando en consola.
     */
    public function handle()
    {
        $this->info("INFO: --- Iniciando Limpieza del Entorno de Pruebas ---");

        // Mensaje de confirmación del comando
        $mensajePeligro = "ATENCIÓN: ESTA ACCIÓN ES IRREVERSIBLE. Se eliminarán bases de datos y se vaciarán tablas.";
        if (!$this->confirm($mensajePeligro . " ¿Está seguro de que desea continuar?", false)) {
            $this->warn("Operación cancelada por el usuario.");
            return 1;
        }

        // Obtenemos el nombre de la conexión principal
        $conexionPrincipal = config('database.default');

        // 1. ELIMINAR BASES DE DATOS DE PROYECTO
        $this->comment("\n1. Eliminando Bases de Datos de Proyecto...");

        // Obtener la lista de BDs de proyecto de la tabla de metadatos
        $proyectos = Proyecto::all();

        if ($proyectos->isEmpty()) {
            $this->warn("No se encontraron bases de datos de proyecto registradas para eliminar.");
        } else {
            foreach ($proyectos as $proyecto) {
                $dbName = $proyecto->conexion;
                try {
                    // Eliminación física de la BD
                    DB::connection($conexionPrincipal)->statement("DROP DATABASE IF EXISTS `{$dbName}`");
                    $this->info("Base de datos '{$dbName}' eliminada.");
                } catch (\Exception $e) {
                    $this->error("Error al eliminar la BD {$dbName}: " . $e->getMessage());
                }
            }
        }

        // 2. LIMPIAR TABLAS ESPECÍFICAS EN GALILEO
        $this->comment("\n2. Vaciando tablas específicas en la BD Galileo ({$conexionPrincipal})...");

        try {
            // Desactivar temporalmente las FK (necesario para TRUNCATE/DELETE si hay referencias)
            DB::connection($conexionPrincipal)->statement('SET FOREIGN_KEY_CHECKS=0;');

            // a) Vaciar tabla bases_de_datos (usando TRUNCATE)
            DB::connection($conexionPrincipal)->table('bases_de_datos')->truncate();
            $this->info("Tabla 'bases_de_datos' vaciada.");

            // b) Vaciar tabla profesores
            Profesor::truncate();
            $this->info("Tabla 'profesores' vaciada.");

            Empresa::truncate();
            $this->info("Tabla 'empresas' vaciada.");

            TutorLaboral::truncate();
            $this->info("Tabla 'tutores laborales' vaciada.");

            User::truncate();
            $this->info("Tabla 'users' vaciada.");

            // Reactivar las FK
            DB::connection($conexionPrincipal)->statement('SET FOREIGN_KEY_CHECKS=1;');
        } catch (\Exception $e) {
            $this->error("Error al vaciar las tablas en Galileo: " . $e->getMessage());
            return 1;
        }

        $this->info("\n--- Limpieza del Entorno de Pruebas Completada. ---");
        return 0;
    }
}
