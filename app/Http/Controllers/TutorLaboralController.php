<?php

namespace App\Http\Controllers;

use App\Models\TutorLaboral;
use App\Models\Empresa;
use App\Models\Alumno;
use App\Models\Proyecto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;

class TutorLaboralController extends Controller
{
    public function indexTutoresLaborales()
    {
        // El modelo TutorLaboral usa la conexión principal (Galileo) por defecto
        $tutores = TutorLaboral::all(); 

        return view('tutores.index', compact('tutores'));
    }

    public function mostrarAlumnos()
    {
        // 1. Obtenemos el tutor laboral central (BD Galileo)
        $tutorLaboral_id = Auth::id();

        $tutor = TutorLaboral::findOrFail($tutorLaboral_id);
        
        $alumnosTotales = collect();
        
        $config_base = config('database.connections.' . config('database.default'));
        
        // 2. Obtenemos todas las bases de datos de proyecto registradas y visibles
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
            $alumnos = Alumno::query()
                ->where('tutor_laboral_id', $tutorLaboral_id)->get();
            
            // 4. Agregamos los resultados a la colección global
            $alumnosTotales = $alumnosTotales->merge($alumnos);
        }

        // 5. Devolvemos la conexión de Alumno a la principal (limpieza de la conexión)
        Alumno::getConnectionResolver()->setDefaultConnection(config('database.default'));

        return view('alumno.index', [
            'tutor' => $tutor, 
            'alumnos' => $alumnosTotales
        ]);
    }

    /**
     * Obtenemos el listado de alumnos del tutor laboral
     */
    public function indexAlumnosTutorizados()
    {
        // 1. Obtener el ID del tutor laboral actualmente autenticado.
        $tutorLaboral_id = Auth::id();

        // 2. Obtenemos el objeto TutorLaboral para pasarlo a la vista si es necesario
        $tutor = TutorLaboral::findOrFail($tutorLaboral_id);

        $alumnosTotales = collect();
        
        // Configuraciones base para las conexiones dinámicas
        $config_base = config('database.connections.' . config('database.default'));
        
        // 3. Obtenemos todas las bases de datos de proyecto registradas
        $proyectos = Proyecto::where('finalizado', 0)->get(); //

        foreach ($proyectos as $proyecto) {
            $conexion_proyecto_nombre = 'proyecto_temp_' . $proyecto->id_base_de_datos; 
            
            // 4. Configurar y forzar la conexión dinámica para el proyecto actual
            $config_base['database'] = $proyecto->conexion;
            config(["database.connections.{$conexion_proyecto_nombre}" => $config_base]);

            Alumno::getConnectionResolver()->setDefaultConnection($conexion_proyecto_nombre); //
            
            // 5. Filtramos los alumnos por el ID del tutor laboral logueado
            // La consulta se ejecuta en la base de datos del proyecto actual
            $alumnos = Alumno::query()
                ->where('tutor_laboral_id', $tutorLaboral_id)
                ->get();
            
            // 6. Agregamos los resultados a la colección global
            $alumnosTotales = $alumnosTotales->merge($alumnos); //
        }

        // 7. Devolver la conexión de Alumno a la principal (limpieza)
        Alumno::getConnectionResolver()->setDefaultConnection(config('database.default'));

        // 8. Retornar la vista con los alumnos filtrados
        return view('alumno.index', [
            'tutor' => $tutor, 
            'alumnos' => $alumnosTotales
        ]);
    }
}