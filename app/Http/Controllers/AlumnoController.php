<?php

namespace App\Http\Controllers;

use App\Models\Modulo;
use App\Models\Tarea;
use App\Models\Alumno;
use App\Models\Proyecto; // ¡Necesario para obtener todas las BDs!
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
                // Configurar la conexión dinámica
                $baseConfig = config('database.connections.mysql');
                $newConfig = $baseConfig;
                $newConfig['database'] = $dbName; // Usamos el nombre de la BD del proyecto

                // Sobrescribir o añadir la conexión dinámica a la configuración
                // Esto permite que 'Alumno::on($conexion)' funcione.
                Config::set("database.connections.{$conexion}", $newConfig); 
                
                // Ahora puedes usarla:
                $alumnos_proyecto = Alumno::on($conexion)->get();
                
                // 4. Agregar resultados a la colección global
                $alumnos_totales = $alumnos_totales->merge($alumnos_proyecto);

            } catch (\Exception $e) {
                // Si la conexión falla (BD no existe/credenciales erróneas), 
                // no detenemos la ejecución, sino que ignoramos este proyecto.
            } finally {
                // Es CRÍTICO limpiar la configuración dinámica después de usarla
                // para no interferir con el resto de la aplicación.
                Config::offsetUnset("database.connections.{$conexion}");
            }
        }

        // Renombramos la variable para el compact
        $alumnos = $alumnos_totales;

        return view('alumno.index', compact('alumnos'));
    }
    // public function showAlumno(Request $request, $alumno_id)
    // {
    //     $proyectos = Proyecto::where('finalizado', 0)->get(); 
    //     $alumno = null;
    //     $conexionEncontrada = null;

    //     foreach ($proyectos as $proyecto) {
    //         $conexion = $proyecto->conexion;
    //         $dbName = $proyecto->proyecto;

    //         try {
    //             // 1. Configurar la conexión dinámica temporalmente
    //             $baseConfig = config('database.connections.mysql');
    //             $newConfig = $baseConfig;
    //             $newConfig['database'] = $dbName;
    //             Config::set("database.connections.{$conexion}", $newConfig); 

    //             // 2. Intentar buscar al alumno en esta base de datos
    //             $alumno = Alumno::on($conexion)
    //                 ->where('id_alumno', $alumno_id)
    //                 ->with([
    //                     'tutorLaboral', // Tutor Laboral (BD central)
    //                     'tutorDocente', // Tutor Docente (BD central)
    //                     'modulos', // Módulos (BD proyecto)
    //                     'tareas' => function ($query) {
    //                         $query->with('criterios'); // Cargamos los criterios de cada tarea (BD proyecto)
    //                     }
    //                 ])
    //                 ->first();
                
    //             // 3. Si encontramos al alumno, detenemos la búsqueda
    //             if ($alumno) {
    //                 $conexionEncontrada = $conexion;
    //                 $alumno->setConnection($conexionEncontrada); // Asigna la conexión para futuras relaciones
                    
    //                 // No eliminamos la conexión. La dejamos activa porque el alumno la necesita.
    //                 break; 
    //             }
        
    //         } catch (\Exception $e) {
    //             // Manejo de errores de conexión (opcional): si la BD no existe, simplemente continuamos.
    //             // Esto evita que una BD mal configurada detenga el bucle.
    //         } finally {
    //             // Si el alumno NO se encontró, eliminamos la conexión dinámica.
    //             if (!$alumno) {
    //                 Config::offsetUnset("database.connections.{$conexion}");
    //             }
    //         }
    //     }

    //     // Si el alumno no se encontró en ninguna BD activa
    //     if (!$alumno) {
    //         return redirect()->route('alumno.index')->with('error', 'El alumno solicitado no fue encontrado o el proyecto no está activo.');
    //     }

    //     // 4. El alumno encontrado es un objeto Eloquent. Lo pasamos a la vista.
    //     return view('alumno.show', compact('alumno', 'conexionEncontrada'));
    // }

    public function showAlumno(Request $request, $alumno_id)
    {
        $proyectos = Proyecto::where('finalizado', 0)->get(); 
        $alumno = null;
        $conexionEncontrada = null;
        $conexionesConfiguradas = []; // Almacenar las conexiones que se crearon

        try {
            foreach ($proyectos as $proyecto) {
                $conexion = trim($proyecto->conexion); 
                $dbName = $proyecto->proyecto;

                // 1. CONFIGURACIÓN DINÁMICA
                $baseConfig = config('database.connections.mysql');
                $newConfig = $baseConfig;
                $newConfig['database'] = $dbName;
                Config::set("database.connections.{$conexion}", $newConfig); 
                $conexionesConfiguradas[] = $conexion; // Añadimos la conexión a la lista

                DB::purge($conexion);
                DB::connection($conexion);

                // 2. BUSCAR AL ALUMNO (Sin Eager Loading local)
                $alumno = Alumno::on($conexion)
                    ->where('id_alumno', $alumno_id)
                    ->with(['tutorLaboral', 'tutorDocente']) 
                    ->first();
                
                // 3. Si encontramos al alumno
                if ($alumno) {
                    $conexionEncontrada = $conexion;
                    $alumno->setConnection($conexionEncontrada); 
                    
                    // Cargar Módulos y Tareas (usando la conexión asignada)
                    $alumno->load(['modulos']); 
                    $alumno->load([
                        'tareas' => function ($query) {
                            $query->with('criterios');
                        }
                    ]);
                    
                    break; // Rompemos el bucle. La conexión queda en Config::set().
                }
                
            } // Fin del foreach

        } catch (\Exception $e) {
            // En caso de error inesperado, el finally manejará la limpieza
            // throw $e; // Si quieres ver el error completo
        } finally {
            // 4. LIMPIEZA FINAL: Solo eliminamos las conexiones que NO se usaron.
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
        
        // La conexión $conexionEncontrada permanece configurada para la vista.
        return view('alumno.show', compact('alumno', 'conexionEncontrada'));
    }
}