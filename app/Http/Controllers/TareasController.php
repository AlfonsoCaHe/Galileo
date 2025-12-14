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
     * Método para redirigir a la vista de tareas de un alumno
     */
    public function tareasIndex($proyecto_id, $alumno_id){
        $profesor = Auth::user()->rolable;

        $proyecto = Proyecto::findOrFail($proyecto_id);

        $this->setDynamicConnection($proyecto_id);

        $alumno = Alumno::where('id_alumno', $alumno_id)
            ->with('tareas');

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

        // 4. VALIDACIONES DE SEGURIDAD

        // 4.1. ¿La tarea pertenece al alumno que intenta editarla?
        if ($tarea->alumno_id !== $alumno_id_logueado) {
            return redirect()->back()->withErrors('ERROR: No tienes permiso para modificar esta tarea.');
        }

        // 4.2.) ¿La tarea está bloqueada por el profesor?
        if ($tarea->bloqueado) {
            return redirect()->back()->withErrors('ERROR: Esta tarea está bloqueada y ya no se puede modificar.');
        }

        // 5. SI CORRECTO VALIDAMOS LOS DATOS ---
        
        $request->validate([
            'notas_alumno' => 'required|string|max:250',
            'fecha' => 'required|date',
            'duracion' => 'required|string|max:5',
        ]);

        // --- ACTUALIZACIÓN ---

        try {
            $tarea->update([
                'notas_alumno' => $request->notas_alumno,
                'fecha' => $request->fecha,
                'duracion' => $request->duracion
            ]);

            // Redirigimos atrás (back) con mensaje de éxito
            return redirect()->back()->with('success', 'Tarea actualizada correctamente.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput() // Mantiene lo que el alumno escribió
                ->withErrors('Error al guardar: ' . $e->getMessage());
        }
    }

/* |--------------------------------------------------------------------------
    | Métodos AJAX
    |-------------------------------------------------------------------------- */

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
     * Actualiza solo la DURACIÓN vía AJAX.
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
     * Bloquea o Desbloquea todas las tareas hermanas a la vez (AJAX).
     * 
     * DEBE SER REVISADO SU FUNCIONAMIENTO
     */
    public function toggleBloqueoMasivo(Request $request, $proyecto_id, $tarea_id)
    {
        $request->validate(['bloqueado' => 'required|boolean']);

        $this->setDynamicConnection($proyecto_id);

        try {
            $tareaOrigen = Tarea::findOrFail($tarea_id);

            // ACTUALIZACIÓN: Usamos actividad_id para agrupar, es más seguro que el nombre string
            Tarea::where('modulo_id', $tareaOrigen->modulo_id)
                 ->where('actividad_id', $tareaOrigen->actividad_id) // Mejor que 'nombre'
                 ->update(['bloqueado' => $request->bloqueado]);

            return response()->json(['success' => true, 'message' => 'Estado actualizado para todos.']);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Actualiza la calificación (APTO/NO APTO) vía AJAX.
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
     * Actualiza el estado de BLOQUEO vía AJAX.
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
     * Método AJAX genérico para actualizar campos de texto (como notas_alumno).
     */
    public function updateNotas(Request $request, $proyecto_id, $tarea_id)
    {
        // 1. Validación flexible
        $request->validate([
            'notas_alumno' => 'nullable|string|max:255',
        ]);

        $this->setDynamicConnection($proyecto_id);

        try {
            $tarea = Tarea::findOrFail($tarea_id);

            // 2. Detectamos qué campo viene en el request y lo actualizamos
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