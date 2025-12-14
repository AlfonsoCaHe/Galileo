<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\Modulo;
use App\Models\Actividad;
use App\Models\Tarea;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class ActividadesController extends Controller
{
    /**
     * Configura la conexión dinámica para bases de datos dinámicas.
     */
    private function setDynamicConnection($proyecto_id)
    {
        $proyecto = Proyecto::findOrFail($proyecto_id);
        $connectionName = 'dynamic_' . $proyecto->id_base_de_datos;
        
        $config = config('database.connections.mysql');
        $config['database'] = $proyecto->conexion;
        Config::set("database.connections.{$connectionName}", $config);
        DB::purge($connectionName);

        // Configuramos la conexión por defecto para los modelos implicados
        Actividad::getConnectionResolver()->setDefaultConnection($connectionName);
        Modulo::getConnectionResolver()->setDefaultConnection($connectionName);
        Tarea::getConnectionResolver()->setDefaultConnection($connectionName);
    }

    /**
     * Listado de ACTIVIDADES del módulo
     */
    public function index($proyecto_id, $modulo_id)
    {
        $this->setDynamicConnection($proyecto_id);
        $modulo = Modulo::findOrFail($modulo_id);

        // Obtenemos las actividades directamente con sus ras y criterios asociados
        $actividades = Actividad::with('criterios.ras')
                        ->where('modulo_id', $modulo_id)
                        ->orderBy('created_at', 'desc')
                        ->get();

        return view('gestion.actividades.index', compact('proyecto_id', 'modulo', 'actividades'));
    }

    /**
     * Método que redirige al formulario de creación de actividades
     */
    public function create($proyecto_id, $modulo_id)
    {
        $this->setDynamicConnection($proyecto_id);
        
        $modulo = Modulo::with(['ras.criterios'])->findOrFail($modulo_id);
        
        return view('gestion.actividades.create', compact('proyecto_id', 'modulo'));
    }

    /**
     * Almacena la nueva actividad en la tabla 'actividades'.
     */
    public function store(Request $request, $proyecto_id, $modulo_id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tarea' => 'nullable|string', // Texto del desplegable
            'criterios' => 'nullable|array'
        ]);

        $this->setDynamicConnection($proyecto_id);

        try {
            DB::transaction(function () use ($request, $modulo_id) {
                
                // Creamos la actividad
                $actividad = Actividad::create([
                    'nombre' => $request->nombre,
                    'descripcion' => $request->descripcion,
                    'tarea' => $request->tarea,
                    'modulo_id' => $modulo_id,
                ]);

                // Uso de la tabla pivote nueva (actividad_criterio)
                if ($request->has('criterios')) {
                    $actividad->criterios()->attach($request->criterios);
                }
            });

            if(auth()->user()->isAdmin()){
                return redirect()->route('gestion.actividades.index', ['proyecto_id' => $proyecto_id, 'modulo_id' => $modulo_id])
                    ->with('success', 'Actividad creada correctamente.');
            }else{
                return redirect()->route('profesores.modulos.alumnos', ['proyecto_id' => $proyecto_id, 'modulo_id' => $modulo_id])
                    ->with('success', 'Actividad creada correctamente.');
            }

        } catch (\Exception $e) {
            return redirect()->back()->withErrors('Error al crear la actividad: ' . $e->getMessage());
        }
    }

    /**
     * FormMétodo que redirige al formulario de edición de la actividad
     */
    public function edit($proyecto_id, $modulo_id, $actividad_id)
    {
        $this->setDynamicConnection($proyecto_id);
        
        $modulo = Modulo::with('ras.criterios')->findOrFail($modulo_id);
        $actividad = Actividad::with('criterios')->findOrFail($actividad_id);

        // Obtenemos los IDs de la pivote para marcar los checkbox
        $criteriosIds = $actividad->criterios->pluck('id_criterio')->toArray();

        return view('gestion.actividades.edit', compact('proyecto_id', 'modulo', 'actividad', 'criteriosIds'));
    }

    /**
     * Método que actualiza la actividad en la base de datos
     */
    public function update(Request $request, $proyecto_id, $modulo_id, $actividad_id)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'tarea' => 'nullable|string',
            'criterios' => 'nullable|array'
        ]);

        $this->setDynamicConnection($proyecto_id);

        try {
            // Ahora sí, $actividad_id tiene el valor correcto
            $actividad = Actividad::findOrFail($actividad_id);

            $actividad->update([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'tarea' => $request->tarea,
            ]);

            // Sincronización con la tabla pivote
            if ($request->has('criterios')) {
                $actividad->criterios()->sync($request->criterios);
            } else {
                $actividad->criterios()->detach();
            }

            return redirect()->route('gestion.actividades.index', [
                'proyecto_id' => $proyecto_id, 
                'modulo_id' => $modulo_id // Podemos usar la variable directa
            ])->with('success', 'Actividad actualizada correctamente.');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors('Error al actualizar: ' . $e->getMessage());
        }
    }

    /**
     * Eliminar actividad.
     */
    public function destroy($proyecto_id, $actividad_id)
    {
        $this->setDynamicConnection($proyecto_id);

        //Comprobamos que no haya tareas con esta actividad, si las hubiera, no podremos eliminarla sin eliminar primero las tareas
        $actividad = Actividad::findOrFail($actividad_id);
         
         
        if ($actividad->tareas()->exists()) {
            return redirect()->back()->withErrors('No es posible eliminar la actividad "'. $actividad->nombre .'" porque ya existen tareas generadas por alumnos asociadas a ella. '.'Debes eliminar primero las tareas de los alumnos.');
        }
         
        Actividad::findOrFail($actividad_id)->delete();
         
        return redirect()->back()->with('success', 'Actividad eliminada.');
    }
}