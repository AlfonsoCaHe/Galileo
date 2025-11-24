<?php

namespace App\Http\Controllers;

use App\Models\Alumno;
use App\Models\Criterio;
use App\Models\Modulo;
use App\Models\ProfesorModulo;
use App\Models\Ras;
use App\Models\Tarea;
use App\Models\Proyecto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ProyectoController extends Controller
{
    public function index(Request $request) 
    {
        // 1. Obtener todos los proyectos
        $proyectos = Proyecto::all(); 

        // 2. Determinamos el estado inicial del filtro para pasarlo a la vista
        // Si el usuario llega con estado finalizado, lo aplicaremos. Por defecto, 'todos'.
        $estado_filtro = $request->get('estado', 'todos');

        // 3. Pasamos todos los proyectos y el estado del filtro a la vista
        return view('gestion.proyectos.index', compact('proyectos', 'estado_filtro'));
    }

    /**
     * Método para cambiar el estado del proyecto de activo a finalizado
     */
    public function updateEstado(Request $request, $proyecto_id)
    {
        // 1. Buscar el proyecto en la BD Galileo
        $proyecto = Proyecto::findOrFail($proyecto_id);

        // 2. Validamos el estado
        $request->validate([
            'finalizado' => 'required|boolean',
        ]);
        
        // 3. Actualizamos el campo 'finalizado'
        $proyecto->update([
            'finalizado' => $request->finalizado,
        ]);

        // 4. Devolvemos una respuesta JSON (para la solicitud AJAX)
        return response()->json([
            'success' => true,
            'message' => 'Estado del proyecto ' . $proyecto->proyecto . ' actualizado con éxito.',
            'nuevo_estado' => $proyecto->finalizado,
        ]);
    }

    /**
     * Almacena un nuevo proyecto llamando al comando de Artisan CrearProyectoNuevoBD.
     */
    public function store(Request $request)
    {
        // 1. Validación
        $validated = $request->validate([
            'year_start' => 'required|integer|min:2020|max:' . (now()->year + 1),// Solo permitimos de 2020 al año próximo al actual
        ]);

        try {
            // 2. Llamada al comando
            $exitCode = Artisan::call('db:crear-proyecto', [ 
                'year_start' => $validated['year_start'],
            ]);

            // 3. Revisar el código de salida del comando
            if ($exitCode !== 0) {
                // Si el comando devuelve un error (ej. falta de permisos, fallo en migración)
                $output = Artisan::output();
                Log::error("Comando 'db:crear-proyecto' falló. Salida: {$output}");
                
                return redirect()->back()->withInput()->withErrors(['creacion_bd' => 'Error al ejecutar el comando de creación de proyecto.']);
            }
            $end_year = $validated['year_start'] + 2;
            $nombre_proyecto = 'Proyecto ' . $validated['year_start'] . '-' . $end_year;

            return redirect()->route('gestion.proyectos.index')->with('success', "{$nombre_proyecto} creado, base de datos generada y migraciones ejecutadas con éxito.");

        } catch (\Exception $e) {
            // Manejo de errores de ejecución de PHP o Artisan
            Log::error("Error inesperado en ProyectoController@store: " . $e->getMessage());
            return redirect()->back()->withInput()->withErrors(['general' => 'Error inesperado al crear el proyecto: ' . $e->getMessage()]);
        }
    }

    /**
     * Verifica si una base de datos de proyecto está vacía, excluyendo la tabla 'migrations'.
     */
    private function checkProjectDatabaseEmpty(Proyecto $proyecto): bool
    {
        $physicalDatabaseName = $proyecto->conexion;
        // 1. Definir el nombre de conexión registrado dinámicamente
        $registeredConnectionName = 'proyecto_temp_' . $proyecto->id_base_de_datos; 

        // 2. Registrar la conexión dinámica (lógica extraída de setDynamicConnection)
        try {
            $config_base = config('database.connections.' . config('database.default'));
            $config_base['database'] = $physicalDatabaseName;
            config(["database.connections.{$registeredConnectionName}" => $config_base]);
            
            // Clave que MySQL devuelve en SHOW TABLES (ej. Tables_in_proyecto_UUID)
            $tablesKey = 'Tables_in_' . $physicalDatabaseName; 

            // 3. Obtener todas las tablas en la BD del proyecto usando el NOMBRE DE CONEXIÓN REGISTRADO
            $tables = DB::connection($registeredConnectionName)->select('SHOW TABLES');

            $tablesToCheck = [];
            foreach ($tables as $table) {
                // Acceso dinámico a la propiedad del objeto devuelto por MySQL
                $tableName = $table->{$tablesKey}; 
                
                // Excluir la tabla 'migrations' y 'failed_jobs'
                if ($tableName !== 'migrations' && $tableName !== 'failed_jobs') {
                    $tablesToCheck[] = $tableName;
                }
            }

            // 4. Contar registros en cada tabla
            foreach ($tablesToCheck as $tableName) {
                // CRÍTICO: Usar el nombre de conexión REGISTRADO para el conteo
                $count = DB::connection($registeredConnectionName)->table($tableName)->count();
                
                if ($count > 0) {
                    Log::warning("Intento de eliminar proyecto {$proyecto->proyecto}: La tabla '{$tableName}' contiene {$count} registros.");
                    return false; // Se encontraron datos
                }
            }
            
            return true; // Tablas de datos vacías

        } catch (\Exception $e) {
            // En caso de fallo de conexión o error de sentencia
            Log::error("Error de conexión/verificación al eliminar proyecto {$proyecto->proyecto}: " . $e->getMessage());
            return false; 
        } finally {
            // 5. Limpieza: Eliminar la conexión registrada dinámicamente
            // Esto evita que se mantenga en memoria para posteriores peticiones.
            config(["database.connections.{$registeredConnectionName}" => null]);
        }
    }


    /**
     * Elimina el registro del proyecto y su base de datos física, tras verificar que esté vacía.
     */
    public function destroy($proyecto_id)
    {
        // 1. Encontrar el Proyecto en la BD principal
        $proyecto = Proyecto::findOrFail($proyecto_id);
        
        // 2. Ejecutamos la verificación
        if (!$this->checkProjectDatabaseEmpty($proyecto)) {
            // Si la BD NO está vacía
            return redirect()->route('gestion.proyectos.index')
                             ->withErrors("El proyecto '{$proyecto->proyecto}' no puede ser eliminado. La base de datos asociada contiene datos en sus tablas.");
        }

        $nombre_proyecto = $proyecto->proyecto;
        $nombre_bd_conexion = $proyecto->conexion;

        try {
        // 3. Eliminar la BD física (Usando el comando Artisan con --no-interaction)
        
            // CRÍTICO: Usamos 'Artisan::call' con la opción '--no-interaction' (o '-n') para evitar la doble confirmación
            // y los fallos en el contexto HTTP.
            $exitCode = Artisan::call('db:eliminar-bd', [
                'nombre_bd' => $nombre_bd_conexion,
                '--no-interaction' => true, // Esta opción fuerza el modo no interactivo
            ]);

            if ($exitCode !== 0) {
                // Si el comando falla (ej. problemas de conexión/permisos de DROP)
                $output = Artisan::output();
                Log::error("Fallo al eliminar BD física '{$nombre_bd_conexion}' vía Artisan. Salida: {$output}");
                return redirect()->back()->withErrors("Error crítico: Falló la eliminación física de la base de datos '{$nombre_bd_conexion}'. Revise logs.");
            }

            // 4. Eliminar el registro del proyecto de la BD principal (Galileo)
            $proyecto->delete();
            
            return redirect()->route('gestion.proyectos.index')
                            ->with('success', "Proyecto '{$nombre_proyecto}' eliminado con éxito. Se eliminó la base de datos física '{$nombre_bd_conexion}'.");

        } catch (\Exception $e) {
            Log::error("Error al eliminar el proyecto {$proyecto->proyecto}: " . $e->getMessage());
            // No es necesario llamar a restoreConnection aquí ya que el controlador no usa setDynamicConnection antes.
            return redirect()->back()->withErrors('Error al eliminar el proyecto: ' . $e->getMessage());
        }
    }

    // Método auxiliar para configurar la conexión dinámica
    private function setDynamicConnection($proyecto_id)
    {
        $proyecto = Proyecto::findOrFail($proyecto_id);
        $conexion_nombre = 'proyecto_temp_' . $proyecto->id_base_de_datos;
        $config_base = config('database.connections.' . config('database.default'));
        
        $config_base['database'] = $proyecto->conexion;
        config(["database.connections.{$conexion_nombre}" => $config_base]);

        // Forzamos a los modelos dinámicos a usar esta conexión
        Alumno::getConnectionResolver()->setDefaultConnection($conexion_nombre);
        Modulo::getConnectionResolver()->setDefaultConnection($conexion_nombre);
        Tarea::getConnectionResolver()->setDefaultConnection($conexion_nombre);
        Ras::getConnectionResolver()->setDefaultConnection($conexion_nombre);
        Criterio::getConnectionResolver()->setDefaultConnection($conexion_nombre);
        ProfesorModulo::getConnectionResolver()->setDefaultConnection($conexion_nombre);

        return $proyecto;
    }
    
    // Método auxiliar para restaurar la conexión
    private function restoreConnection()
    {
        // Restaurar la conexión predeterminada (Galileo)
        Alumno::getConnectionResolver()->setDefaultConnection(config('database.default'));
        Modulo::getConnectionResolver()->setDefaultConnection(config('database.default'));
        Tarea::getConnectionResolver()->setDefaultConnection(config('database.default'));
        Ras::getConnectionResolver()->setDefaultConnection(config('database.default'));
        Criterio::getConnectionResolver()->setDefaultConnection(config('database.default'));
        ProfesorModulo::getConnectionResolver()->setDefaultConnection(config('database.default'));
    }
}