<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Proyecto;
use App\Models\Modulo;
use App\Models\Profesor;
use App\Models\User;
use App\Models\Alumno;
use App\Models\Tarea;
use App\Models\TutorLaboral;
use App\Models\Actividad;
use Illuminate\Notifications\Action;

class ProfesoradoDocenteController extends Controller
{
    /**
     * Método para configurar la conexión dinámica y los modelos.
     */
    private function setDynamicConnection($proyecto_id)
    {
        $proyecto = Proyecto::where('id_base_de_datos', $proyecto_id)->firstOrFail();
        $connectionName = 'dynamic_' . $proyecto->id_base_de_datos;
        
        $config = config('database.connections.mysql');
        $config['database'] = $proyecto->conexion;
        Config::set("database.connections.{$connectionName}", $config);
        DB::purge($connectionName);

        Tarea::getConnectionResolver()->setDefaultConnection($connectionName);
        Modulo::getConnectionResolver()->setDefaultConnection($connectionName);
        Actividad::getConnectionResolver()->setDefaultConnection($connectionName);
        Alumno::getConnectionResolver()->setDefaultConnection($connectionName);
        
        return $connectionName;
    }

    /**
     * Muestra el listado de módulos asignados al profesor de todas las bases de datos activas.
     */
    public function indexModulos()
    {
        $profesor = Auth::user()->rolable;
        
        // 1. Obtenemos todos los proyectos activos
        $proyectos = Proyecto::where('finalizado', false)->get();
        $modulos = collect();

        // 2. Iteramos sobre cada proyecto para extraer los módulos del profesor
        foreach ($proyectos as $proyecto) {
            try {
                $this->setDynamicConnection($proyecto->id_base_de_datos);

                $modulosData = Modulo::join('profesor_modulo', 'modulos.id_modulo', '=', 'profesor_modulo.modulo_id')
                    ->where('profesor_modulo.profesor_id', $profesor->id_profesor)
                    ->select('modulos.id_modulo', 'modulos.nombre')
                    ->selectRaw('(SELECT COUNT(*) FROM alumno_modulo WHERE alumno_modulo.modulo_id = modulos.id_modulo AND alumno_modulo.deleted_at IS NULL) as alumnos_count')
                    ->get();

                // Añadimos los datos que necesitamos para la vista
                foreach($modulosData as $mod) {
                    $mod->nombre_proyecto = $proyecto->proyecto;
                    $mod->id_proyecto_galileo = $proyecto->id_base_de_datos;
                    $modulos->push($mod);
                }

            } catch (\Exception $e) {
                error_log("Error conectando a proyecto {$proyecto->proyecto}: " . $e->getMessage());
                continue;
            }
        }
        return view('profesores.modulos', compact('modulos'));
    }

    /**
     * Método que redirige a la vista ver Alumnos de un profesor
     */
    public function verAlumnos($proyecto_id, $modulo_id)
    {
        $proyecto = Proyecto::findOrFail($proyecto_id);
        $this->setDynamicConnection($proyecto_id);

        $modulo = Modulo::where('id_modulo', $modulo_id)->first();

        if (!$modulo) {
            abort(404, 'Módulo no encontrado en este proyecto.');
        }

        $alumnos = Alumno::join('alumno_modulo', 'alumnos.id_alumno', '=', 'alumno_modulo.alumno_id')
            ->where('alumno_modulo.modulo_id', $modulo_id)
            ->whereNull('alumno_modulo.deleted_at')
            ->select('alumnos.id_alumno', 'alumnos.nombre')
            ->selectRaw("(SELECT COUNT(*) 
                        FROM tareas 
                        WHERE tareas.alumno_id = alumnos.id_alumno AND tareas.modulo_id = '{$modulo_id}') as tareas_count")
            ->get();
        
        foreach($alumnos as $alumno){
            $user = User::where('rolable_id', $alumno->id_alumno)
                    ->where('rolable_type', Alumno::class)
                    ->firstOrFail();

            $alumno->email = $user->email;
        }

        $actividades = Actividad::where('modulo_id', $modulo_id)
            ->orderBy('nombre', 'asc')
            ->select('actividades.*')
            ->selectRaw("(SELECT COUNT(*) 
                        FROM tareas, actividades 
                        WHERE tareas.actividad_id = actividades.id_actividad AND tareas.modulo_id = '{$modulo_id}') as actividades_count")
            ->get();

            $actividades = Actividad::all();

        return view('profesores.alumnos', compact('alumnos', 'modulo', 'proyecto', 'actividades'));
    }

    /**
     * Método para mostrar un listado de las tareas del alumno en un determinado módulo
     */
    public function verTareasAlumno($proyecto_id, $modulo_id, $alumno_id)
    {
        $proyecto = Proyecto::findOrFail($proyecto_id);
        $this->setDynamicConnection($proyecto_id);
        
        $alumno = Alumno::where('id_alumno', $alumno_id)->first();

        if (!$alumno) {
            abort(404, 'Alumno no encontrado.');
        }

        $modulo = Modulo::where('id_modulo', $modulo_id)->first();

        $tareas = Tarea::where('alumno_id', $alumno_id)
            ->where('modulo_id', $modulo_id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Asignamos la información de la actividad para la vista
        foreach($tareas as $tarea){
            $tarea->actividad = Actividad::where('id_actividad', $tarea->actividad_id)->first();
        }

        return view('profesores.alumnos_tareas', compact('alumno', 'modulo', 'tareas', 'proyecto'));
    }

    /**
     * Método que redirige a la vista para la creación de una nueva tarea
     */
    public function crearActividad($proyecto_id, $modulo_id)
    {
        $this->setDynamicConnection($proyecto_id);

        $modulo = Modulo::where('id_modulo', $modulo_id)
            ->with('ras')
            ->first();

        return view('gestion.actividades.create', compact('proyecto_id', 'modulo'));
    }

    /**
     * Método para redirigir a la vista que modifica la tarea de un alumno
     */
    public function editTarea($proyecto_id, $tarea_id){
        $this->setDynamicConnection($proyecto_id);

        $tarea = Tarea::where('id_tarea', $tarea_id);

        $modulo_id = $tarea->modulo_id;

        return view('profesores.tarea_edit', compact('proyecto_id', 'modulo_id', 'tarea'));
    }

    /**
     * Método para redirigir a la vista de alumnos de los que el profesor es tutor docente
     */
    public function tutorizados()
    {
        $profesor = Auth::user()->rolable->id_profesor;
        
        // Obtenemos proyectos activos
        $proyectos = Proyecto::where('finalizado', false)->get();
        
        $alumnosTutorizados = collect();

        foreach ($proyectos as $proyecto) {
            try {
                $this->setDynamicConnection($proyecto->id_base_de_datos);

                $alumnos = Alumno::with('modulos')
                    ->where('tutor_docente_id', $profesor)
                    ->whereHas('modulos', function($q) {
                        $q->whereNull('alumno_modulo.deleted_at');//Solo para alumnos activos
                    })
                    ->get();

                // Para obtener el mail desde galileo
                foreach ($alumnos as $alumno) {
                    // 1. Buscamos el USUARIO en la BD Principal
                    $user = User::find($alumno->usuario_id);
                    
                    // 2. Buscamos el TUTOR LABORAL en la BD Principal
                    $tutor = TutorLaboral::find($alumno->tutor_laboral_id);
                    
                    // 3. Asignamos los datos al alumno
                    $alumno->alumno_email = $user ? $user->email : 'Sin email asociado';
                    
                    // Pasamos el objeto completo del tutor (o null si no tiene)
                    // Esto te permitirá acceder en la vista a $alumno->tutor_laboral->nombre, ->email, etc.
                    $alumno->tutor_laboral = $tutor; 
                    
                    // 4. Metadatos del proyecto (para las rutas y el badge de la vista)
                    $alumno->proyecto_id = $proyecto->id_base_de_datos;
                    $alumno->proyecto_nombre = $proyecto->proyecto;

                    // 5. Añadimos a la colección final
                    $alumnosTutorizados->push($alumno);
                }

            } catch (\Exception $e) {
                Log::error("Error obteniendo tutorizados en {$proyecto->conexion}: " . $e->getMessage());
            }
        }

        return view('profesores.tutorizados', compact('alumnosTutorizados'));
    }

    /**
     * Método que redirige a la vista de las tareas de un alumno tutorizado
     */
    public function tareasAlumnoTutorizado($proyecto_id, $alumno_id)
    {
        $proyecto = Proyecto::findOrFail($proyecto_id);

        $this->setDynamicConnection($proyecto_id);
        
        //Comprobamos que el alumno existe y lo obtenemos
        $alumno = Alumno::where('id_alumno', $alumno_id)->first();

        if (!$alumno) abort(404, 'Alumno no encontrado');

        // Obtenemos las tareas del alumno
        $tareas = Tarea::join('modulos', 'tareas.modulo_id', '=', 'modulos.id_modulo')
            ->where('tareas.alumno_id', $alumno_id)
            ->select(
                'tareas.*', 
                'modulos.nombre as nombre_modulo',
                'modulos.id_modulo'
            )
            ->orderBy('tareas.created_at', 'desc')
            ->get();

        // Reutilizamos la vista de tareas, pero le pasamos null en $modulo porque ya no es uno específico
        return view('profesores.tareas_docente', compact('alumno', 'tareas', 'proyecto'))->with('modulo', null);
    }

    /**
     * Método que redirige a la vista de editar los datos del profesor para el profesor
     */
    public function editar($profesor_id)
    {
        // 1. Encontrar al Profesor en la BD principal (Galileo)
        $profesor = Profesor::findOrFail($profesor_id);

        // 2. Encontrar el registro de usuario asociado (para obtener el email)
        $user = User::where('rolable_id', $profesor->id_profesor)
            ->where('rolable_type', Profesor::class)
            ->firstOrFail();

        // 3. Obtenemos el email de User
        $profesor->email = $user->email;

        return view('profesores.edit', compact('profesor'));
    }

    /**
     * Método para modificar la contraseña del profesor por él mismo.
     */
    public function update(Request $request, $profesor_id)
    {
        $profesor = Profesor::findOrFail($profesor_id);
        $user = $profesor->user;

        // 2. Validamos los campos
        $validated = $request->validate([
            'password' => 'nullable|min:8|confirmed',
        ]);

        try {
            // 3. Transacción
            DB::beginTransaction();

            if ($user) {
                // Solo actualiza la contraseña si se proporcionó un valor nuevo
                if (!empty($validated['password'])) {
                    $user->password = $validated['password'];
                }
                $user->save();
            } else {
                // Si por alguna corrupción de datos antigua no tiene usuario, lo logueamos
                Log::warning("Profesor {$profesor->id_profesor} actualizado sin usuario asociado.");
            }

            DB::commit();

            return redirect()->route('profesores.panel')->with('success', 'Datos del profesor ' . $profesor['nombre'] . ' actualizados con éxito.');

        } catch (\Exception $e) {
            DB::rollBack();
            // Logueamos si hay error
            Log::error('Error al cambiar contraseña del Profesor: ' . $e->getMessage());
            
            return redirect()->back()->withInput()->withErrors('Error al actualizar el profesor: ' . $e->getMessage());
        }
    }
}