<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\Modulo;
use App\Models\Tarea;
use App\Models\Alumno;
use App\Models\Ras;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class TareaController extends Controller
{
    private function setDynamicConnection($proyecto_id)
    {
        $proyecto = Proyecto::findOrFail($proyecto_id);
        $connectionName = 'dynamic_' . $proyecto->id_base_de_datos;
        
        $config = config('database.connections.mysql');
        $config['database'] = $proyecto->conexion;
        Config::set("database.connections.{$connectionName}", $config);
        DB::purge($connectionName);

        // Configurar conexión en modelos clave
        Tarea::getConnectionResolver()->setDefaultConnection($connectionName);
        Modulo::getConnectionResolver()->setDefaultConnection($connectionName);
        Alumno::getConnectionResolver()->setDefaultConnection($connectionName);
        Ras::getConnectionResolver()->setDefaultConnection($connectionName);
    }

    public function index($proyecto_id, $modulo_id)
    {
        $this->setDynamicConnection($proyecto_id);
        $modulo = Modulo::findOrFail($modulo_id);

        // Si es profesor/admin, ve todas. Si es alumno, solo las suyas.
        $query = Tarea::with(['alumno', 'criterios'])->where('modulo_id', $modulo_id);
        
        if (Auth::user()->isAlumno()) {
            $alumnoId = Auth::user()->rolable_id; 
            $query->where('alumno_id', $alumnoId);
        }

        $tareas = $query->orderBy('created_at', 'desc')->get();

        return view('gestion.tareas.index', compact('proyecto_id', 'modulo', 'tareas'));
    }

    public function create($proyecto_id, $modulo_id)
    {
        $this->setDynamicConnection($proyecto_id);
        
        $modulo = Modulo::with(['alumnos', 'ras.criterios'])->findOrFail($modulo_id);
        
        return view('gestion.tareas.create', compact('proyecto_id', 'modulo'));
    }

    public function store(Request $request, $proyecto_id, $modulo_id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'alumnos' => 'required|array|min:1', // Al menos 1 alumno seleccionado
            'criterios' => 'nullable|array'      // IDs de criterios seleccionados
        ]);

        $this->setDynamicConnection($proyecto_id);

        try {
            DB::transaction(function () use ($request, $modulo_id) {
                // Iteramos por cada alumno seleccionado para crearle su tarea personal
                foreach ($request->alumnos as $alumno_id) {
                    $tarea = Tarea::create([
                        'nombre' => $request->nombre,
                        'descripcion' => $request->descripcion,
                        'modulo_id' => $modulo_id,
                        'alumno_id' => $alumno_id,
                        'apto' => false,      // Default NO APTO
                        'bloqueado' => false  // Default ABIERTO
                    ]);

                    // Asociar criterios si se seleccionaron
                    if ($request->has('criterios')) {
                        $tarea->criterios()->attach($request->criterios);
                    }
                }
            });

            return redirect()->route('gestion.tareas.index', ['proyecto_id' => $proyecto_id, 'modulo_id' => $modulo_id])
                ->with('success', 'Tareas asignadas correctamente a ' . count($request->alumnos) . ' alumnos.');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors('Error al asignar tareas: ' . $e->getMessage());
        }
    }

    // Método especial para bloquear/desbloquear (Solo Profesor)
    public function toggleBloqueo($proyecto_id, $tarea_id)
    {
        $this->setDynamicConnection($proyecto_id);
        $tarea = Tarea::findOrFail($tarea_id);
        
        // Invertir estado
        $tarea->bloqueado = !$tarea->bloqueado;
        $tarea->save();

        $estado = $tarea->bloqueado ? 'bloqueada' : 'desbloqueada';
        return redirect()->back()->with('success', "Tarea $estado correctamente.");
    }
    
    // Destroy, Update, etc... (Implementar según necesidad estándar)
    public function destroy($proyecto_id, $tarea_id)
    {
         $this->setDynamicConnection($proyecto_id);
         Tarea::findOrFail($tarea_id)->delete();
         return redirect()->back()->with('success', 'Tarea eliminada.');
    }
}