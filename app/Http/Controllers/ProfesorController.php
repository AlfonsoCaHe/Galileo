<?php

namespace App\Http\Controllers;

use App\Models\Profesor;
use App\Models\Alumno;
use App\Models\Proyecto; // ¡Necesario para obtener todas las BDs!
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ProfesorController extends Controller
{
    public function indexProfesores()
    {
        // El modelo Profesor usa la conexión principal (Galileo) por defecto
        $profesores = Profesor::all(); 

        return view('profesor.index', compact('profesores'));
    }

    public function mostrarAlumnos(Request $request, $profesor_id)
    {
        // 1. Obtenemos el profesor central (BD Galileo)
        $profesor = Profesor::findOrFail($profesor_id);
        $filtro = $request->input('filtro', 'docente');
        $alumnosGlobal = collect();
        
        $config_base = config('database.connections.' . config('database.default'));
        
        // 2. Obtenemos todas las bases de datos de proyecto registradas y visibles
        // $proyectos = Proyecto::all(); //Obtenemos todas las bases de datos de proyecto incluidos los finalizados
        $proyectos = Proyecto::where('finalizado', 0)->get();

        foreach ($proyectos as $proyecto) {
            // Aseguramos que id_base_de_datos es el nombre correcto de la PK de Proyecto
            // Aunque ya lo comprobamos al crear la base de datos, se trata de una comprobación adicional
            $conexion_proyecto_nombre = 'proyecto_temp_' . $proyecto->id_base_de_datos; 
            
            // 2.a. Configuramos la conexión dinámica para esta BD de proyecto
            $config_base['database'] = $proyecto->conexion;
            config(["database.connections.{$conexion_proyecto_nombre}" => $config_base]);

            // 2.b. Forzamos el modelo Alumno a usar la BD del proyecto actual
            Alumno::getConnectionResolver()->setDefaultConnection($conexion_proyecto_nombre); 
            
            // 3. Consulta de Alumnos en la BD actual
            $alumnosQuery = Alumno::query();

            if ($filtro === 'todos') {
                // --- Opción A: Alumnos de todos los módulos que imparte ---
                
                $moduloIds = DB::connection($conexion_proyecto_nombre)
                               ->table('profesor_modulo')
                               ->where('profesor_id', $profesor_id)
                               ->pluck('modulo_id');

                $alumnosLocal = $alumnosQuery
                                ->whereHas('modulos', function ($query) use ($moduloIds) {
                                    $query->whereIn('modulo_id', $moduloIds);
                                })->get();

            } else { // $filtro === 'docente'
                // --- Opción B: Solo aquellos de los que es tutor docente ---

                $alumnosLocal = $alumnosQuery
                                ->where('tutor_docente_id', $profesor_id)
                                ->get();
            }

            // Añadimos el nombre del proyecto a cada alumno para la vista
            $alumnosLocal->each(function ($alumno) use ($proyecto) {
                $alumno->proyecto_nombre = $proyecto->proyecto;
            });
            
            // 4. Agregamos los resultados a la colección global
            $alumnosGlobal = $alumnosGlobal->merge($alumnosLocal);
        }

        // 5. Devolvemos la conexión de Alumno a la principal (limpieza de la conexión)
        Alumno::getConnectionResolver()->setDefaultConnection(config('database.default'));

        return view('profesor.alumnos', [
            'profesor' => $profesor, 
            'alumnos' => $alumnosGlobal, 
            'filtro' => $filtro
        ]);
    }
}