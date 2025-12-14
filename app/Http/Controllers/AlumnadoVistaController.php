<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Proyecto;
use App\Models\TutorLaboral;
use App\Models\User;
use App\Models\Profesor;
use App\Models\Modulo;
use App\Models\Tarea;
use App\Models\Actividad;
use App\Models\Alumno;

class AlumnadoVistaController extends Controller
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
     * Vista principal del alumnado
     */
    public function index(){
        $userAlumno = Auth::user()->rolable_id; 

        // Buscamos el proyecto del alumno
        $proyectos = Proyecto::where('finalizado', false)->get();
        $proyecto = null;
        $connectionName = null;
        $tutorLaboral = null;
        $tutorDocente = null;

        foreach ($proyectos as $pro) {
            $tempConnName = 'dynamic_' . $pro->id_base_de_datos;
            
            $config = config('database.connections.mysql');
            $config['database'] = $pro->conexion;
            Config::set("database.connections.{$tempConnName}", $config);
            DB::purge($tempConnName);

            $existe = DB::connection($tempConnName)
                ->table('alumnos')
                ->where('id_alumno', $userAlumno)
                ->exists();

            if ($existe) {
                $proyecto = $pro;
                $connectionName = $tempConnName;
                break;
            }
        }

        if (!$proyecto) {
            abort(404, 'No se ha encontrado matrícula activa para este alumno.');
        }

        // Recuperamos alumno y tutores
        $alumno = DB::connection($connectionName)
            ->table('alumnos')
            ->where('id_alumno', $userAlumno)
            ->first();
        
        $tutorLaboral = TutorLaboral::where('id_tutor_laboral', $alumno->tutor_laboral_id)->first();
        $tutorDocente = Profesor::where('id_profesor',  $alumno->tutor_docente_id)->first();

        // Obtenemos módulos
        $modulos = DB::connection($connectionName)
            ->table('modulos')
            ->join('alumno_modulo', 'modulos.id_modulo', '=', 'alumno_modulo.modulo_id')
            ->where('alumno_modulo.alumno_id', $userAlumno)
            ->whereNull('alumno_modulo.deleted_at')
            ->select('modulos.id_modulo', 'modulos.nombre')
            ->get();

        $alumno->tutor_docente = $tutorDocente->nombre;
        $alumno->tutor_laboral = $tutorLaboral->nombre;

        return view('alumnos.panel', compact('proyecto', 'modulos', 'alumno'));
    }

    /**
     * Método que devuelve a la vista de tareas realizadas (bloqueadas)
     */
    public function tareasRealizadas($proyecto_id){
        $userAlumno = Auth::user()->rolable_id;

        $proyecto = Proyecto::where('id_base_de_datos', $proyecto_id)->first();

        $this->setDynamicConnection($proyecto_id);

        // Obtenemos módulos del alumno
        $alumno = Alumno::with('modulos')->findOrFail($userAlumno);
        $modulos = $alumno->modulos; 

        // Buscamos las tareas que están bloqueadas
        $tareasRealizadas = Tarea::with(['modulo', 'actividad']) // Cargamos relación para ver nombre real de actividad
            ->where('alumno_id', $userAlumno)
            ->where('bloqueado', true)
            ->orderBy('fecha', 'desc')
            ->get()
            ->map(function($tarea) {
                // Mapeo para ajustar a lo que espera tu vista si es necesario
                return (object) [
                    'id_tarea' => $tarea->id_tarea,
                    'nombre_tarea' => $tarea->tarea,
                    'notas_alumno' => $tarea->notas_alumno,
                    'fecha' => $tarea->fecha,
                    'duracion' => $tarea->duracion,
                    'apto' => $tarea->apto,
                    'nombre_modulo' => $tarea->modulo->nombre
                ];
            });

        return view('alumnos.tareas_realizadas', compact('proyecto', 'modulos', 'tareasRealizadas'));
    }

    /**
     * Método que redirige a la vista de tareas pendientes (No bloqueadas)
     */
    public function tareasPendientes($proyecto_id){
        $userAlumno = Auth::user()->rolable_id;

        $proyecto = Proyecto::where('id_base_de_datos', $proyecto_id)->first();

        $this->setDynamicConnection($proyecto_id);

        $alumno = Alumno::with('modulos')->findOrFail($userAlumno);
        $modulos = $alumno->modulos;

        // Buscamos las tareas que no están bloqueadas
        $tareasNoRealizadas = Tarea::with(['modulo', 'actividad'])
            ->where('alumno_id', $userAlumno)
            ->where('bloqueado', false)
            ->orderBy('fecha', 'desc')
            ->get()
            ->map(function($tarea) {
                return (object) [
                    'id_tarea' => $tarea->id_tarea,
                    'nombre_tarea' => $tarea->tarea,
                    'notas_alumno' => $tarea->notas_alumno,
                    'fecha' => $tarea->fecha,
                    'duracion' => $tarea->duracion,
                    'apto' => $tarea->apto,
                    'modulo_id' => $tarea->modulo_id,
                    'nombre_modulo' => $tarea->modulo->nombre
                ];
            });

        return view('alumnos.tareas_pendientes', ['proyecto' => $proyecto, 'modulos' => $modulos, 'tareasDisponibles' => $tareasNoRealizadas]);
    }

     /**
     * Método que redirige a la vista para crear una tarea
     */
    public function crearTarea($proyecto_id)
    {
        $userAlumno = Auth::user()->rolable_id;
        
        $proyecto = Proyecto::where('id_base_de_datos', $proyecto_id)->first();

        $this->setDynamicConnection($proyecto_id);
        
        $alumno = Alumno::find($userAlumno);

        //El resto de elementos que necesitamos se cargarán directamente con el componente <x-desplegable-actividades/>

        return view('alumnos.create_tarea', compact('alumno', 'proyecto'));
    }

    /**
     * Método para almacenar la tarea recién creada
     * Permite múltiples registros para la misma actividad_id
     */
    public function storeTarea(Request $request, $proyecto_id)
    {
        $userAlumno = Auth::user()->rolable_id;

        $proyecto = Proyecto::findOrFail($proyecto_id);
        
        $request->validate([
            'actividad_id' => 'required|string', 
            'notas_alumno' => 'required|string',
            'duracion' => 'required|string',
            'fecha' => 'required|date',
        ]);

        try {
            $this->setDynamicConnection($proyecto_id);
            $actividad = Actividad::findOrFail($request->actividad_id);

            $modulo_id = $actividad->modulo_id;

            $nuevaTarea = Tarea::create([
                'alumno_id' => $userAlumno,
                'modulo_id' => $modulo_id,
                'actividad_id' => $request->actividad_id,
                'nombre' => $actividad->nombre, 
                'tarea' => $actividad->tarea,
                'notas_alumno' => $request->notas_alumno,
                'fecha' => $request->fecha,
                'duracion' => $request->duracion,
            ]);

            $mensaje = 'Tarea enviada correctamente';

            return redirect()->route('alumnado.tareas_pendientes', compact('proyecto_id'))->with('success', $mensaje);

        } catch (\Exception $e) {
            return redirect()->back()->withInput()->withErrors('Error al guardar: ' . $e->getMessage());
        }
    }

    /**
     * Método que redirige a la vista de edición de la tarea (Solo si no está bloqueada)
     */
    public function editTarea($proyecto_id, $modulo_id, $tarea_id){
        $userAlumno = Auth::user()->rolable_id;
        
        $this->setDynamicConnection($proyecto_id);

        $proyecto = Proyecto::where('id_base_de_datos', $proyecto_id)->first();
        $modulo = Modulo::findOrFail($modulo_id);
        
        // Buscamos la tarea y verificamos que sea del alumno
        $tareaPrincipal = Tarea::where('id_tarea', $tarea_id)
                               ->where('alumno_id', $userAlumno)
                               ->firstOrFail();

        if ($tareaPrincipal->bloqueado) {
            return redirect()->back()->with('error', 'No puedes editar una tarea que ya ha sido enviada/bloqueada.');
        }

        return view('alumnos.edit_tarea', compact('proyecto', 'modulo', 'tareaPrincipal'));
    }

    /**
     * Método para actualizar la tarea (Solo antes de ser bloqueada)
     */
    public function updateTarea(Request $request, $proyecto_id, $tarea_id)
    {
        $userAlumno = Auth::user()->rolable_id;

        $request->validate([
            'notas_alumno' => 'required|string',
            'fecha' => 'required|date',
            'duracion' => 'nullable|string',
        ]);

        try {
            $this->setDynamicConnection($proyecto_id);

            $tarea = Tarea::where('id_tarea', $tarea_id)
                          ->where('alumno_id', $userAlumno)
                          ->firstOrFail();

            if ($tarea->bloqueado) {
                return redirect()->route('alumnado.tareas_pendientes', compact('proyecto_id'))->with('error', 'La tarea ya está bloqueada.');
            }

            $tarea->update([
                'duracion' => $request->duracion,
                'fecha' => $request->fecha,
                'notas_alumno' => $request->notas_alumno
            ]);
            
            return redirect()->route('alumnado.tareas_pendientes', compact('proyecto_id'))->with('success', 'Tarea actualizada correctamente.');

        } catch (\Exception $e) {
            return redirect()->back()->withInput()->withErrors('Error al actualizar: ' . $e->getMessage());
        }
    }

    /**
     * Método que permite al alumno eliminar una tarea no bloqueada de la base de datos
     */
    public function destroyTarea($proyecto_id, $tarea_id)
    {
        $userAlumno = Auth::user()->rolable_id;

        try {
            $this->setDynamicConnection($proyecto_id);

            // Buscamos la tarea del alumno
            $tarea = Tarea::where('id_tarea', $tarea_id)
                          ->where('alumno_id', $userAlumno)
                          ->firstOrFail();

            // Seguridad: Solo permitir borrar si no está bloqueado
            if ($tarea->bloqueado) {
                return redirect()->back()->with('error', 'No puedes eliminar una tarea que ya ha sido terminada.');
            }

            // Desvinculamos criterios antes de borrar (limpieza pivote)
            $tarea->criterios()->detach();
            
            $tarea->delete();

            return redirect()->route('alumnos.tareasPendientes', ['proyecto_id' => $proyecto_id])
                             ->with('success', 'Borrador eliminado correctamente.');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors('Error al eliminar: ' . $e->getMessage());
        }
    }

    /**
     * Métodos para actualizar la contraseña del alumno
     */
    public function editar($proyecto_id){
        $userAlumno = Auth::user()->rolable_id;
        $user = User::where('rolable_id',$userAlumno)->first();

        $this->setDynamicConnection($proyecto_id);
        
        $proyecto = Proyecto::where('id_base_de_datos', $proyecto_id)->first();
        $alumno = Alumno::find($userAlumno);
        $alumno->email = $user->email;

        return view('gestion.alumnos.edit', compact('proyecto', 'alumno'));
    }

    public function update(Request $request, $proyecto_id, $alumno_id)
    {
        $user = User::where('rolable_id', $alumno_id)->firstOrFail();
        $validated = $request->validate(['password' => 'nullable|min:8|confirmed']);

        try {
            $this->setDynamicConnection($proyecto_id); 

            if (!empty($validated['password'])) {
                $user->update($validated);
            }  

            return redirect()->route('alumnos.panel')->with('success', 'Datos actualizados.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->withErrors('Error: ' . $e->getMessage());
        }
    }
}