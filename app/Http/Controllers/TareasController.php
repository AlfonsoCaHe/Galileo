<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\Modulo;
use App\Models\Tarea;
use App\Models\Alumno;
use App\Models\Ras;
use App\Models\Actividad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TareasController extends Controller
{
    /**
     * Establece la conexión dinámica con la base de datos del proyecto.
     */
    private function setDynamicConnection($proyecto_id)
    {
        $proyecto = Proyecto::findOrFail($proyecto_id);
        $connectionName = 'dynamic_' . $proyecto->id_base_de_datos;
        
        $config = config('database.connections.mysql');
        $config['database'] = $proyecto->conexion;
        Config::set("database.connections.{$connectionName}", $config);
        DB::purge($connectionName);

        Tarea::getConnectionResolver()->setDefaultConnection($connectionName);
        Modulo::getConnectionResolver()->setDefaultConnection($connectionName);
        Alumno::getConnectionResolver()->setDefaultConnection($connectionName);
        Ras::getConnectionResolver()->setDefaultConnection($connectionName);
    }

    /**
     * Método auxiliar para restaurar la conexión
     */
    private function restoreConnection()
    {
        // Restaurar la conexión predeterminada (Galileo)
        Tarea::getConnectionResolver()->setDefaultConnection(config('database.default'));
        Modulo::getConnectionResolver()->setDefaultConnection(config('database.default'));
        Alumno::getConnectionResolver()->setDefaultConnection(config('database.default'));
        Ras::getConnectionResolver()->setDefaultConnection(config('database.default'));
    }

    /**
     * Método para redirigir a la vista de tareas de un alumno
     */
    public function tareasIndex($proyecto_id, $alumno_id){
        $profesor = Auth::user()->rolable;

        $proyecto = Proyecto::findOrFail($proyecto_id);

        $this->setDynamicConnection($proyecto_id);

        // Adjuntamos las tareas del alumno
        $alumno = Alumno::where('id_alumno', $alumno_id)->with('tareas');

        return view(route('profesores.alumnos.tareas', compact('proyecto', 'alumno')));
    }

    /**
     * Método para actualizar los datos de una tarea, solo si no está bloqueada
     */
    public function updateTarea(Request $request, $proyecto_id, $tarea_id)
    {
        // 1. Identificamos al usuario logueado
        $alumno_id_logueado = Auth::user()->rolable_id;

        // 2. Establecemos conexión
        $proyecto = Proyecto::findOrFail($proyecto_id);
        $this->setDynamicConnection($proyecto_id);

        // 3. Buscamos la tarea
        $tarea = Tarea::findOrFail($tarea_id);

        // 4. Validaciones de seguridad
        // 4.1. ¿La tarea pertenece al alumno que intenta editarla?
        if ($tarea->alumno_id !== $alumno_id_logueado) {
            return redirect()->back()->withErrors('ERROR: No tienes permiso para modificar esta tarea.');
        }

        // 4.2. ¿La tarea está bloqueada por el profesor?
        if ($tarea->bloqueado) {
            return redirect()->back()->withErrors('ERROR: Esta tarea está bloqueada y ya no se puede modificar.');
        }

        // 5. Si es correcto validamos y actualizamos ---
        
        $request->validate([
            'notas_alumno' => 'required|string|max:250',
            'fecha' => 'required|date',
            'duracion' => 'required|string|max:5',
        ]);

        try {
            $tarea->update([
                'notas_alumno' => $request->notas_alumno,
                'fecha' => $request->fecha,
                'duracion' => $request->duracion
            ]);

            // Redirigimos atrás con mensaje de éxito
            return redirect()->back()->with('success', 'Tarea actualizada correctamente.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput() // Mantiene lo que el alumno escribió
                ->withErrors('Error al guardar: ' . $e->getMessage());
        }
    }

    /**
     * AJAX: Método para actulizar la fecha en caliente
     */
    public function updateFecha(Request $request, $proyecto_id, $tarea_id)
    {
        $request->validate([
            'fecha' => 'nullable|date'
        ]);

        $this->setDynamicConnection($proyecto_id);
        
        try {
            $tarea = Tarea::findOrFail($tarea_id);
            
            // Si llega fecha vacía, guardamos null, si no, la fecha
            $tarea->fecha = $request->fecha ?: null;
            $tarea->save();

            return response()->json(['success' => true, 'message' => 'Fecha guardada']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * AJAX: Actualiza la duración en caliente
     */
    public function updateDuracion(Request $request, $proyecto_id, $tarea_id)
    {
        $request->validate([
            'duracion' => 'nullable|string|max:5' // Ej: "01:30"
        ]);

        $this->setDynamicConnection($proyecto_id);
        
        try {
            $tarea = Tarea::findOrFail($tarea_id);
            $tarea->duracion = $request->duracion;
            $tarea->save();

            return response()->json(['success' => true, 'message' => 'Duración guardada']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * AJAX: Bloquea o Desbloquea todas las tareas hermanas a la vez
     * 
     * OPCIÓN ELIMINADA DEL FORMULARIO
     */
    public function toggleBloqueoMasivo(Request $request, $proyecto_id, $tarea_id)
    {
        $request->validate(['bloqueado' => 'required|boolean']);

        $this->setDynamicConnection($proyecto_id);

        try {
            $tareaOrigen = Tarea::findOrFail($tarea_id);

            // Usamos actividad_id para agrupar, es más seguro que el nombre string
            Tarea::where('modulo_id', $tareaOrigen->modulo_id)
                 ->where('actividad_id', $tareaOrigen->actividad_id)
                 ->update(['bloqueado' => $request->bloqueado]);

            return response()->json(['success' => true, 'message' => 'Estado actualizado para todos.']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * AJAX: Actualiza la calificación (APTO/NO APTO) en caliente
     */
    public function updateApto(Request $request, $proyecto_id, $tarea_id)
    {
        // Validamos que llegue un booleano (1, 0, "true", "false")
        $request->validate([
            'apto' => 'required|boolean'
        ]);

        $this->setDynamicConnection($proyecto_id);
        
        try {
            $tarea = Tarea::findOrFail($tarea_id);
            $tarea->apto = $request->apto;
            $tarea->save();

            return response()->json([
                'success' => true, 
                'message' => 'Calificación guardada',
                'estado' => $tarea->apto ? 'APTO' : 'NO APTO'
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * AJAX: Actualiza la calificación y modifica el campo APTO en caliente
     * Es para el selector de calificación del tutor
     */
    public function updateCalificacion(Request $request, $proyecto_id, $tarea_id)
    {
        // Buscamos el proyecto para asegurarnos que es válido
        $proyecto = Proyecto::findOrFail($proyecto_id);
        // Establecemos la conexión
        $this->setDynamicConnection($proyecto_id);
        // Buscamos la tarea
        $tarea = Tarea::findOrFail($tarea_id);

        // Validamos que llegue un número de 0 a 10
        $request->validate([
            'calificacion' => 'required|integer|min:0|max:10'
        ]);

        // Obtenemos el valor
        $nota = $request->input('calificacion');

        // Si la nota >= 5 es APTO (1), si no, NO APTO (0)
        $esApto = $nota >= 5 ? 1 : 0;

        // Actualizamos AMBOS campos a la vez
        $tarea->update([
            'calificacion' => $nota,
            'apto' => $esApto
        ]);

        return response()->json([
            'success' => true, 
            'mensaje' => 'Calificación guardada',
            'apto' => $esApto
        ]);
    }

    /**
     * AJAX: Actualiza el estado de bloque en caliente
     */
    public function updateBloqueo(Request $request, $proyecto_id, $tarea_id)
    {
        $request->validate([
            'bloqueado' => 'required|boolean'
        ]);

        $this->setDynamicConnection($proyecto_id);
        
        try {
            $tarea = Tarea::findOrFail($tarea_id);
            $tarea->bloqueado = $request->bloqueado;
            $tarea->save();

            return response()->json([
                'success' => true, 
                'message' => $tarea->bloqueado ? 'Tarea Bloqueada' : 'Tarea Desbloqueada'
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * AJAX: Método AJAX genérico para actualizar campos de texto (como las notas_alumno).
     * Finalmente solo se utiliza para las notas de los alumnos
     */
    public function updateNotas(Request $request, $proyecto_id, $tarea_id)
    {
        // Validación de texto
        $request->validate([
            'notas_alumno' => 'nullable|string|max:255',
        ]);

        $this->setDynamicConnection($proyecto_id);

        try {
            $tarea = Tarea::findOrFail($tarea_id);

            // Detectamos qué campo viene en el request y lo actualizamos
            if ($request->has('notas_alumno')) {
                $tarea->notas_alumno = $request->notas_alumno;
            }

            $tarea->save();

            return response()->json([
                'success' => true, 
                'message' => 'Datos actualizados correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}