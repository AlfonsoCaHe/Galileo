<?php

namespace App\Http\Controllers;

// use App\Models\Modulo;
// use App\Models\Tarea;
use App\Models\Alumno;
use App\Models\Proyecto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

class AlumnoController extends Controller
{
    /**
     * Listamos todos los alumnos de las bases de datos activas
     */
    public function indexAlumno()
    {
        $proyectos = Proyecto::where('finalizado', 0)->get();

        $alumnos_totales = new Collection(); 
        
        foreach ($proyectos as $proyecto) {
            $conexion = $proyecto->conexion;
            $dbName = $proyecto->proyecto; // El campo 'proyecto' guarda el nombre de la BD
            
            try {
                // Configuramos la conexión dinámica
                $baseConfig = config('database.connections.mysql');
                $newConfig = $baseConfig;
                $newConfig['database'] = $dbName; // Usamos el nombre de la BD del proyecto

                // Sobrescribimos o añadir la conexión dinámica a la configuración
                // Esto permite que 'Alumno::on($conexion)' funcione.
                Config::set("database.connections.{$conexion}", $newConfig); 
                
                $alumnos_proyecto = Alumno::on($conexion)->get();
                
                // Agregamos los resultados a la colección global
                $alumnos_totales = $alumnos_totales->merge($alumnos_proyecto);

            } catch (\Exception $e) {
                // Si la conexión falla (BD no existe/credenciales erróneas), no detenemos la ejecución, sino que ignoramos este proyecto.
            } finally {
                // Hay que limpiar la configuración dinámica después de usarla para no interferir con el resto de la aplicación.
                Config::offsetUnset("database.connections.{$conexion}");
            }
        }

        // Renombramos la variable para el compact
        $alumnos = $alumnos_totales;

        return view('alumno.index', compact('alumnos'));
    }

    /**
     * Obtenemos la información del alumno que pasamos por parámetro y redirigimos a la vista
     */
    public function showAlumno(Request $request, $alumno_id)
    {
        $proyectos = Proyecto::where('finalizado', 0)->get(); 
        $alumno = null;
        $conexionEncontrada = null;
        $conexionesConfiguradas = []; // Almacenamos las conexiones que se vamos a ir creando

        try {
            foreach ($proyectos as $proyecto) {
                $conexion = trim($proyecto->conexion); 
                $dbName = $proyecto->proyecto;

                // 1. Configuración dinámica
                $baseConfig = config('database.connections.mysql');
                $newConfig = $baseConfig;
                $newConfig['database'] = $dbName;
                Config::set("database.connections.{$conexion}", $newConfig); 
                $conexionesConfiguradas[] = $conexion; // Una vez establecida, la añadimos a la lista

                DB::purge($conexion);
                DB::connection($conexion);

                // 2. Buscamos al alumno
                $alumno = Alumno::on($conexion)
                    ->where('id_alumno', $alumno_id)
                    ->with(['tutorLaboral', 'tutorDocente']) 
                    ->first();
                
                // 3. Si encontramos al alumno
                if ($alumno) {
                    $conexionEncontrada = $conexion;
                    $alumno->setConnection($conexionEncontrada); 
                    
                    // Cargamos sus Módulos y Tareas (usando la conexión asignada)
                    $alumno->load(['modulos']); 
                    $alumno->load([
                        'tareas' => function ($query) {
                            $query->with('criterios');
                        }
                    ]);
                    
                    break; // Rompemos el bucle. La conexión queda en Config::set().
                }    
            }

        } catch (\Exception $e) {
            // En caso de error inesperado, el finally manejará la limpieza
            // throw $e; // Comentado para no detener la ejecución de la aplicación, si hay algún error descomentar
        } finally {
            // 4. Eliminamos las conexiones que no se usaron y por tanto no necesitaremos.
            foreach ($conexionesConfiguradas as $conn) {
                if ($conn !== $conexionEncontrada) {
                    Config::offsetUnset("database.connections.{$conn}"); 
                    DB::purge($conn);
                }
            }
        }

        if (!$alumno) {
            return redirect()->route('alumno.index')->with('error', 'El alumno solicitado no fue encontrado o el proyecto no está activo.');
        }
        
        // La conexión $conexionEncontrada permanece configurada en la vista.
        return view('alumno.show', compact('alumno', 'conexionEncontrada'));
    }
}