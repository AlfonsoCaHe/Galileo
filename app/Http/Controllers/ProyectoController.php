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
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\AlumnosModulosImport;

class ProyectoController extends Controller
{
    /* 
    * Método auxiliar para configurar la conexión dinámica
    */
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
    
    /**
     * Método auxiliar para restaurar la conexión
     */
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

    /**
     * Método que redirige a la vista de proyectos
     */
    public function index(Request $request) 
    {
        // 1. Obtenemos todos los proyectos
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
        // 1. Buscamos el proyecto en la BD Galileo
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
        // 1. Validación del formulario
        $request->validate([
            'year_start'    => 'required|integer|digits:4',
            'archivo_excel' => 'nullable|file|mimes:xlsx,xls,csv'
        ]);

        $yearStart = $request->year_start;
        
        // 2. Ejecutamos el comando creado para crear la estructura de un nuevo proyecto
        // Crea la BD física, el registro en 'bases_de_datos' y las tablas mediante el support ProjectSchemaManager
        try {
            $exitCode = Artisan::call('db:crear-proyecto', [
                'year_start' => $yearStart
            ]);

            // Si el comando devuelve algo distinto de 0, es que hubo un error (ej: ya existe un proyecto con ese nombre)
            if ($exitCode !== 0) {
                $error = Artisan::output();
                return back()->with('error', 'Error al crear proyecto: ' . $error);
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Excepción al ejecutar comando: ' . $e->getMessage());
        }

        // 3. Recuperamos el proyecto recién creado
        // El comando genera el nombre como "proyecto_2025_2027" (app/console/commands/CrearProyectoNuevoBD.php)
        $yearEnd = (int)$yearStart + 2;
        $nombreProyectoEsperado = "proyecto_{$yearStart}_{$yearEnd}";
        
        $proyecto = Proyecto::where('proyecto', $nombreProyectoEsperado)->first();

        if (!$proyecto) {
            return back()->with('error', 'La base de datos se creó, pero no se pudo recuperar el registro del proyecto.');
        }

        // 4. Procesamos un Excel (si se subió alguno para la creación)
        if ($request->hasFile('archivo_excel')) {
            try {
                // Pasamos el objeto $proyecto al importador para que sepa dónde conectar
                Excel::import(new AlumnosModulosImport($proyecto), $request->file('archivo_excel'));
                
                return back()->with('success', "Proyecto '$nombreProyectoEsperado' creado y alumnos importados correctamente.");
            } catch (\Exception $e) {
                // Si falla el Excel, el proyecto YA existe, así que solo avisamos que no se importado el Excel
                Log::error("Error importando excel: " . $e->getMessage());
                return back()->with('warning', "Proyecto creado con éxito, pero falló la importación de alumnos: " . $e->getMessage());
            }
        }

        return back()->with('success', "Proyecto '$nombreProyectoEsperado' creado correctamente (sin alumnos).");
    }

    /**
     * Verifica si una base de datos de proyecto está vacía.
     */
    private function checkProjectDatabaseEmpty(Proyecto $proyecto): bool
    {
        $nombreConexion = 'proyecto_temp_' . $proyecto->id_base_de_datos; 

        try {
            $this->setDynamicConnection($proyecto->id_base_de_datos);
            
            $nombreFisico = $proyecto->conexion;
            $tablesKey = 'Tables_in_' . $nombreFisico; 

            $tablas = DB::connection($nombreConexion)->select('SHOW TABLES');// Para seleccionar todas las tablas del proyecto

            foreach ($tablas as $tabla) {
                $tableName = $tabla->{$tablesKey}; 
                
                if (!in_array($tableName, ['migrations', 'failed_jobs'])) {
                    $count = DB::connection($nombreConexion)->table($tableName)->count();
                    
                    if ($count > 0) {
                        Log::warning("Proyecto {$proyecto->proyecto} tiene datos en '{$tableName}'.");
                        return false; 
                    }
                }
            }
            return true; 

        } catch (\Exception $e) {
            Log::error("Error verificando proyecto {$proyecto->proyecto}: " . $e->getMessage());
            return false; 
        } finally {
            // Restauramos y limpiamos la configuración
            $this->restoreConnection();
            config(["database.connections.{$nombreConexion}" => null]);
        }
    }


    /**
     * Elimina el registro del proyecto y su base de datos física, tras verificar que esté vacía.
     */
    public function destroy($proyecto_id)
    {
        // 1. Encontramos el Proyecto en la BD Galileo
        $proyecto = Proyecto::findOrFail($proyecto_id);
        
        // 2. Ejecutamos la verificación de si está o no vacía
        if (!$this->checkProjectDatabaseEmpty($proyecto)) {
            // Si la BD NO está vacía salimos
            return redirect()->route('gestion.proyectos.index')->withErrors("El proyecto '{$proyecto->proyecto}' no puede ser eliminado. La base de datos asociada contiene datos en sus tablas.");
        }

        $nombre_proyecto = $proyecto->proyecto;
        $nombre_bd_conexion = $proyecto->conexion;

        try {
            // 3. Eliminamos la BD física usando el comando Artisan creado con --no-interaction para evitar la doble confirmación (app/console/commands/borrarproyecto.php)
            $exitCode = Artisan::call('db:eliminar-proyecto', [
                'nombre_proyecto' => $nombre_bd_conexion,
                '--no-interaction' => true, // Esta opción fuerza el modo no interactivo, de lo contrario habría preguntas por comandos
            ]);

            if ($exitCode !== 0) {
                // Si el comando falla (ej. problemas de conexión/permisos de DROP)
                $output = Artisan::output();
                Log::error("Fallo al eliminar BD física '{$nombre_bd_conexion}' vía Artisan. Salida: {$output}");
                return redirect()->back()->withErrors("Error crítico: Falló la eliminación física de la base de datos '{$nombre_bd_conexion}'. Revise logs.");
            }

            // 4. Eliminamos el registro del proyecto de la tabla de proyectos de la BD Galileo
            $proyecto->delete();
            
            return redirect()->route('gestion.proyectos.index')->with('success', "Proyecto '{$nombre_proyecto}' eliminado con éxito. Se eliminó la base de datos física '{$nombre_bd_conexion}'.");

        } catch (\Exception $e) {
            Log::error("Error al eliminar el proyecto {$proyecto->proyecto}: " . $e->getMessage());
            // No es necesario llamar a restoreConnection aquí ya que el controlador no usa setDynamicConnection antes.
            return redirect()->back()->withErrors('Error al eliminar el proyecto: ' . $e->getMessage());
        }
    }
}