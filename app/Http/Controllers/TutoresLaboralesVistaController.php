<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Proyecto;
use App\Models\Profesor;
use App\Models\User;
use App\Models\Alumno;
use App\Models\Tarea;
use App\Models\TutorLaboral;
use App\Models\Actividad;
use Illuminate\Notifications\Action;

class TutoresLaboralesVistaController extends Controller
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
        Actividad::getConnectionResolver()->setDefaultConnection($connectionName);
        Alumno::getConnectionResolver()->setDefaultConnection($connectionName);
        
        return $connectionName;
    }

    /**
     * Método auxiliar para restaurar la conexión
     */
    private function restoreConnection()
    {
        // Restaurar la conexión predeterminada (Galileo)
        Tarea::getConnectionResolver()->setDefaultConnection(config('database.default'));
        Actividad::getConnectionResolver()->setDefaultConnection(config('database.default'));
        Alumno::getConnectionResolver()->setDefaultConnection(config('database.default'));
    }

    /**
     * Método para redirigir a la vista de alumnos de los que es tutor laboral
     */
    public function tutorizados()
    {
        // Obtenemos el tutor laboral logueado
        $tutor_laboral = Auth::user()->rolable->id_tutor_laboral;
        
        // Obtenemos todos los proyectos activos
        $proyectos = Proyecto::where('finalizado', false)->get();
        
        $alumnosTutorizados = collect();

        foreach ($proyectos as $proyecto) {
            try {
                $this->setDynamicConnection($proyecto->id_base_de_datos);

                $alumnos = Alumno::with('modulos')// Obtenemos los alumnos del proyecto que son tutorizados por el tutor laboral logueado
                    ->where('tutor_laboral_id', $tutor_laboral)
                    ->whereHas('modulos', function($q) {
                        $q->whereNull('alumno_modulo.deleted_at');// Solo para alumnos activos
                    })
                    ->get();

                // Para obtener el mail desde galileo
                foreach ($alumnos as $alumno) {
                    // Buscamos el USUARIO en la BD Galileo
                    $user = User::where('rolable_id',$alumno->id_alumno)
                        ->first();

                    // Buscamos el TUTOR LABORAL en la BD Galileo
                    $tutor = TutorLaboral::find($alumno->tutor_laboral_id);
                    
                    // Asignamos los datos al alumno
                    $alumno->email = $user ? $user->email : 'Sin email asociado';
                    
                    // Pasamos el objeto completo del tutor (o null si no tiene)
                    // Esto permite acceder en la vista a $alumno->tutor_laboral->nombre, ->email, etc.
                    $alumno->tutor_laboral = $tutor; 
                    
                    // Datos de la conexión del proyecto para las rutas
                    $alumno->proyecto_id = $proyecto->id_base_de_datos;
                    $alumno->proyecto_nombre = $proyecto->proyecto;

                    // Añadimos el nombre del profesor docente del centro
                    $alumno->profesor = Profesor::where('id_profesor', $alumno->tutor_docente_id)->value('nombre');

                    // Añadimos a la colección final
                    $alumnosTutorizados->push($alumno);
                }

            } catch (\Exception $e) {
                Log::error("Error obteniendo tutorizados en {$proyecto->conexion}: " . $e->getMessage());
            }
        }

        return view('tutores_laborales.tutorizados', compact('alumnosTutorizados'));
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

        // Obtenemos todas las tareas del alumno
        $tareas = Tarea::join('modulos', 'tareas.modulo_id', '=', 'modulos.id_modulo')
            ->where('tareas.alumno_id', $alumno_id)
            ->select(
                'tareas.*', 
                'modulos.nombre as nombre_modulo',
                'modulos.id_modulo'
            )
            ->orderBy('tareas.created_at', 'desc')
            ->get();

        // Rellenamos el resto de información de la vista desde Actividades
        foreach($tareas as $tarea){
            $actividad = Actividad::where('id_actividad', $tarea->actividad_id)->first();
            $tarea->actividadNombre = $actividad->nombre;
            $tarea->actividadTarea = $actividad->tarea;
            $tarea->actividadDescripcion = $actividad->descripcion;
        }

        // Reutilizamos la vista de tareas, pero le pasamos null en $modulo porque ya no es uno específico
        return view('tutores_laborales.tareas_docente', compact('alumno', 'tareas', 'proyecto'))->with('modulo', null);
    }

    /**
     * Método que redirige a la vista de editar los datos del tutor_laboral
     */
    public function editar($tutor_laboral_id)
    {
        // Encontramos al tutor laboral en la BD Galileo
        $tutor = TutorLaboral::findOrFail($tutor_laboral_id);

        // Encontramos el registro de usuario asociado para obtener su email
        $user = User::where('rolable_id', $tutor->id_tutor_laboral)
            ->where('rolable_type', TutorLaboral::class)
            ->firstOrFail();

        // Añadimos su email
        $tutor->email = $user->email;

        return view('tutores_laborales.editar', compact('tutor'));
    }

    /**
     * Método para modificar la contraseña del tutor laboral por él mismo.
     */
    public function update(Request $request, $tutor_laboral_id)
    {
        $tutor_laboral = TutorLaboral::findOrFail($tutor_laboral_id);
        $user = $tutor_laboral->user;

        // Validamos los campos
        $validated = $request->validate([
            'password' => 'nullable|min:8|confirmed',
        ]);

        try {
            // Transacción
            DB::beginTransaction();

            if ($user) {
                // Solo actualiza la contraseña si se proporcionó un valor nuevo
                if (!empty($validated['password'])) {
                    $user->password = $validated['password'];
                }
                $user->save();
            } else {
                // Si por algún motivo no tiene usuario, lo logueamos
                Log::warning("Tutor {$tutor_laboral->id_tutor_laboral} actualizado sin usuario asociado.");
            }

            DB::commit();

            return redirect()->route('tutores_laborales.panel')->with('success', 'Datos del profesor ' . $tutor_laboral['nombre'] . ' actualizados con éxito.');

        } catch (\Exception $e) {
            DB::rollBack();
            // Logueamos si hay error
            Log::error('Error al cambiar contraseña del Profesor: ' . $e->getMessage());
            
            return redirect()->back()->withInput()->withErrors('Error al actualizar el profesor: ' . $e->getMessage());
        }
    }
}