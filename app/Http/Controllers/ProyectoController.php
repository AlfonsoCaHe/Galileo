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
        // 1. Validación del formulario
        $request->validate([
            'year_start'    => 'required|integer|digits:4',
            'archivo_excel' => 'nullable|file|mimes:xlsx,xls,csv'
        ]);

        $yearStart = $request->year_start;
        
        // 2. Ejecutar tu comando existente para crear la estructura
        // Esto crea la BD física, el registro en 'bases_de_datos' y las tablas (ProjectSchemaManager)
        try {
            $exitCode = Artisan::call('db:crear-proyecto', [
                'year_start' => $yearStart
            ]);

            // Si el comando devuelve algo distinto de 0, hubo un error (ej. ya existe)
            if ($exitCode !== 0) {
                $error = Artisan::output();
                return back()->with('error', 'Error al crear proyecto: ' . $error);
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Excepción al ejecutar comando: ' . $e->getMessage());
        }

        // 3. Recuperar el proyecto recién creado
        // Tu comando genera el nombre como "proyecto_2025_2027" (mirando CrearProyectoNuevoBD.php)
        $yearEnd = (int)$yearStart + 2;
        $nombreProyectoEsperado = "proyecto_{$yearStart}_{$yearEnd}";
        
        $proyecto = Proyecto::where('proyecto', $nombreProyectoEsperado)->first();

        if (!$proyecto) {
            return back()->with('error', 'La base de datos se creó, pero no se pudo recuperar el registro del proyecto.');
        }

        // 4. Procesar Excel (si se subió)
        if ($request->hasFile('archivo_excel')) {
            try {
                // Pasamos el objeto $proyecto al importador para que sepa dónde conectar
                Excel::import(new AlumnosModulosImport($proyecto), $request->file('archivo_excel'));
                
                return back()->with('success', "Proyecto '$nombreProyectoEsperado' creado y alumnos importados correctamente.");
            } catch (\Exception $e) {
                // Si falla el Excel, el proyecto YA existe, así que solo avisamos
                Log::error("Error importando excel: " . $e->getMessage());
                return back()->with('warning', "Proyecto creado con éxito, pero falló la importación de alumnos: " . $e->getMessage());
            }
        }

        return back()->with('success', "Proyecto '$nombreProyectoEsperado' creado correctamente (sin alumnos).");
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