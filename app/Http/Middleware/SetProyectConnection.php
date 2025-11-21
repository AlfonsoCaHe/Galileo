<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Proyecto;

/**
 * Establece una conexión dinámica a la base de datos del proyecto usando el ID de proyecto para buscar el nombre real de la BD.
 */
class SetProjectConnection
{
    /**
     * Maneja una petición entrante.
     *
     * @param \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $projectId = Session::get('DB_PROJECT_ID');

        // Solo procedemos si el ID del proyecto está en la sesión.
        if ($projectId) {
            
            // 1. Buscamos el proyecto en la base de datos central ('mysql').
            $project = Proyecto::find($projectId); 

            // Verificamos que se encontró el proyecto y que el campo 'proyecto' no está vacío
            if (!$project || empty($project->proyecto)) {
                error_log("ADVERTENCIA: No se pudo encontrar el Proyecto o el nombre de BD 'proyecto' no está definido con ID: {$projectId}.");
                return $next($request);
            }
            
            // 2. Obtenemos el nombre de la base de datos REAL del campo 'proyecto'
            $databaseName = $project->proyecto;
            
            // 3. Obtenemos la configuración base de la plantilla
            $baseConfig = Config::get('database.connections.course_template');

            if (!$baseConfig) {
                error_log("ADVERTENCIA: La configuración 'course_template' no está definida en database.php.");
                return $next($request);
            }

            // 4. Clonamos y modificamos la configuración base
            $projectConfig = array_merge($baseConfig, [
                'database' => $databaseName,
            ]);

            // 5. Registrar la nueva conexión dinámica: 'mysql_project'
            // Los modelos de Alumno, Profesor, etc., deben usar $connection = 'mysql_project'.
            Config::set('database.connections.mysql_project', $projectConfig);
        }

        return $next($request);
    }
}