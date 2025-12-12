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

    // 
    
    public function index($proyecto_id, $modulo_id)
    {
        $this->setDynamicConnection($proyecto_id);
        $modulo = Modulo::findOrFail($modulo_id);

        // 1. Traemos TODAS las tareas con sus relaciones necesarias
        $todasLasTareas = Tarea::with(['alumno', 'criterios.ras'])
                                ->where('modulo_id', $modulo_id)
                                ->orderBy('created_at', 'desc')
                                ->get();

        // 2. Preparamos los datos del Popover (Alumnos por tarea)
        // Clave: Nombre Tarea -> Valor: {total, html_nombres}
        $infoAlumnos = $todasLasTareas->groupBy('nombre')->map(function ($grupo) {
            return [
                'total' => $grupo->count(),
                // Creamos una lista HTML para el popover
                'nombres' => $grupo->map(function($t) {
                    return "<div>• " . ($t->alumno->nombre ?? 'Sin nombre') . "</div>";
                })->implode('')
            ];
        });

        // 3. Para la tabla visual, usamos 'unique' para mostrar solo UNA fila por actividad
        // Así evitamos que salga la misma tarea repetida 30 veces.
        $tareasUnicas = $todasLasTareas->unique('nombre');

        return view('gestion.tareas.index', compact('proyecto_id', 'modulo', 'tareasUnicas', 'infoAlumnos'));
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
                        'tarea' => $request->tarea,
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
    
    /**
     * Método para eliminar una tarea
     */
    public function destroy($proyecto_id, $tarea_id)
    {
         $this->setDynamicConnection($proyecto_id);
         Tarea::findOrFail($tarea_id)->delete();
         return redirect()->back()->with('success', 'Tarea eliminada.');
    }

    //******************************************************************************************* */
    public function edit($proyecto_id, $modulo_id, $tarea_id)
    {
        $this->setDynamicConnection($proyecto_id);
        $modulo = Modulo::with('ras.criterios')->findOrFail($modulo_id);

        // 1. Buscamos la tarea específica que pinchó el usuario
        $tareaPrincipal = Tarea::with(['criterios'])->findOrFail($tarea_id);

        // 2. Buscamos TODAS las tareas hermanas (mismo nombre y módulo) para listar los alumnos
        // Incluimos las relaciones necesarias para la tabla de alumnos
        $asignaciones = Tarea::with(['alumno', 'alumno.tutorLaboral', 'alumno.tutorDocente'])
                             ->where('modulo_id', $modulo_id)
                             ->where('nombre', $tareaPrincipal->nombre) // Agrupamos por nombre
                             ->get();

        // 3. Obtenemos los IDs de criterios ya asignados para marcar los checkbox
        $criteriosIds = $tareaPrincipal->criterios->pluck('id_criterio')->toArray();

        // Obtenemos los IDs de los alumnos que YA tienen esta tarea
        $idsAlumnosConTarea = $asignaciones->pluck('alumno_id')->toArray();

        // Buscamos alumnos que:
        // A) Estén matriculados en este módulo (usando la relación pivot) y no estén en la lista de los que ya la tienen
        $alumnosDisponibles = Alumno::whereHas('modulos', function($q) use ($modulo_id) {
                                    $q->where('modulos.id_modulo', $modulo_id);
                                })
                                ->whereNotIn('id_alumno', $idsAlumnosConTarea)
                                ->orderBy('nombre')
                                ->get();

        return view('gestion.tareas.edit', compact('proyecto_id', 'modulo', 'tareaPrincipal', 'asignaciones', 'criteriosIds', 'alumnosDisponibles'));
    }

    public function update(Request $request, $proyecto_id, $tarea_id)
    {
        $this->setDynamicConnection($proyecto_id);
        $tarea = Tarea::findOrFail($tarea_id);

        // CASO 1: Actualización MASIVA (Definición General)
        if ($request->modo == 'definicion') {
            
            $request->validate([
                'nombre' => 'required|string',
                'tarea' => 'nullable|string',
                'descripcion' => 'nullable|string',
                'criterios' => 'nullable|array'
            ]);

            // 1. Buscamos todas las tareas hermanas (mismo nombre y modulo) para actualizarlas todas
            // OJO: Usamos el nombre ANTIGUO para buscarlas antes de cambiarlo
            $tareasHermanas = Tarea::where('modulo_id', $tarea->modulo_id)
                                   ->where('nombre', $tarea->nombre)
                                   ->get();

            foreach ($tareasHermanas as $t) {
                // Actualizamos datos básicos
                $t->update([
                    'nombre' => $request->nombre,
                    'tarea' => $request->tarea,
                    'descripcion' => $request->descripcion
                ]);

                // Sincronizamos criterios (pivot)
                if ($request->has('criterios')) {
                    $t->criterios()->sync($request->criterios);
                } else {
                    $t->criterios()->detach();
                }
            }

            return redirect()->back()->with('success', 'Definición actualizada para ' . $tareasHermanas->count() . ' alumnos.');
        }

        // CASO 2: Actualización INDIVIDUAL (Check Apto desde la tabla)
        if ($request->modo == 'individual') {
            // Si el checkbox 'apto' no viene en el request, es que se desmarcó (false)
            $apto = $request->has('apto') ? true : false;
            
            $tarea->update(['apto' => $apto]);
            
            return redirect()->back()->with('success', 'Estado de calificación actualizado.');
        }
        
        return redirect()->back();
    }

    /**
     * Método para mostrar una sola tarea (ACTUALMENTE EN DESUSO)
     */
    public function show($proyecto_id, $modulo_id, $tarea_id){
        $this->setDynamicConnection($proyecto_id);
        $tarea = Tarea::findOrFail($tarea_id);

        return view('gestion.tareas.show', compact('tarea'));
    }

    /**
     * Actualiza solo la fecha vía AJAX (sin recargar).
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
     * Bloquea o Desbloquea TODAS las tareas hermanas a la vez (AJAX).
     */
    public function toggleBloqueoMasivo(Request $request, $proyecto_id, $tarea_id)
    {
        $request->validate(['bloqueado' => 'required|boolean']);

        $this->setDynamicConnection($proyecto_id);

        try {
            $tarea = Tarea::findOrFail($tarea_id);

            // Actualización masiva por Query Builder (más rápido que un foreach)
            Tarea::where('modulo_id', $tarea->modulo_id)
                 ->where('nombre', $tarea->nombre)
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
     * Asigna una tarea existente a nuevos alumnos.
     */
    public function asignarAlumnos(Request $request, $proyecto_id, $tarea_origen_id)
    {
        // 1. Configuramos la conexión manualmente AQUÍ para asegurar que el validador la vea
        $proyecto = Proyecto::findOrFail($proyecto_id);
        
        // Generamos el nombre de conexión
        $nombreConexion = 'proyecto_temp_' . $proyecto->id_base_de_datos;
        
        // Inyectamos la configuración en Laravel en tiempo de ejecución
        $config = config('database.connections.mysql'); // Copiamos la config base
        $config['database'] = $proyecto->conexion;      // Cambiamos la BD a la del proyecto
        Config::set("database.connections.{$nombreConexion}", $config);
        DB::purge($nombreConexion); // Limpiamos caché por si acaso

        // 2. Validación (Ahora sí encontrará la conexión)
        $request->validate([
            'alumnos' => 'required|array',
            // Usamos la conexión que acabamos de registrar explícitamente
            'alumnos.*' => "exists:{$nombreConexion}.alumnos,id_alumno"
        ]);

        try {
            // 3. Ejecutamos la lógica usando la conexión dinámica
            DB::connection($nombreConexion)->transaction(function () use ($tarea_origen_id, $request, $nombreConexion) {
                
                // Leemos la tarea origen en la conexión correcta
                $tareaOrigen = Tarea::on($nombreConexion)->with('criterios')->findOrFail($tarea_origen_id);

                foreach ($request->alumnos as $alumnoId) {
                    // Replicamos la tarea (clona los campos excepto ID y timestamps)
                    $nuevaTarea = $tareaOrigen->replicate(['notas_alumno', 'fecha', 'duracion', 'apto', 'bloqueado', 'alumno_id', 'created_at', 'updated_at', 'deleted_at']);
                    
                    // Asignamos datos nuevos
                    $nuevaTarea->id_tarea = (string) \Illuminate\Support\Str::uuid();
                    $nuevaTarea->alumno_id = $alumnoId;
                    $nuevaTarea->bloqueado = false; 
                    $nuevaTarea->apto = false;
                    
                    // IMPORTANTE: Forzar la conexión en el nuevo modelo antes de guardar
                    $nuevaTarea->setConnection($nombreConexion);
                    $nuevaTarea->save();

                    // Clonamos los criterios (Relación N:M)
                    $criteriosIds = $tareaOrigen->criterios->pluck('id_criterio')->toArray();
                    $nuevaTarea->criterios()->sync($criteriosIds);
                }
            });

            return redirect()->back()->with('success', 'Tarea asignada correctamente a los alumnos seleccionados.');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors('Error al asignar: ' . $e->getMessage());
        }
    }
}