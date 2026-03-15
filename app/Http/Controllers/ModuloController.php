<?php

namespace App\Http\Controllers;

use App\Models\Modulo;
use App\Models\Proyecto;
use App\Models\Profesor;
use App\Models\Alumno;
use App\Models\Ras;
use App\Models\Tarea;
use App\Models\Criterio;
use App\Models\ProfesorModulo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Imports\RasCriteriosImport;
use Maatwebsite\Excel\Facades\Excel;

class ModuloController extends Controller
{
    // Método auxiliar para configurar la conexión dinámica de forma masiva
    private function setDynamicConnection($proyecto_id)
    {
        $proyecto = Proyecto::findOrFail($proyecto_id);
        $conexion_nombre = 'proyecto_temp_' . $proyecto->id_base_de_datos;
        $config_base = config('database.connections.' . config('database.default'));

        $config_base['database'] = $proyecto->conexion;
        config(["database.connections.{$conexion_nombre}" => $config_base]);

        Modulo::getConnectionResolver()->setDefaultConnection($conexion_nombre);
        Alumno::getConnectionResolver()->setDefaultConnection($conexion_nombre);
        Ras::getConnectionResolver()->setDefaultConnection($conexion_nombre);
        Tarea::getConnectionResolver()->setDefaultConnection($conexion_nombre);
        Criterio::getConnectionResolver()->setDefaultConnection($conexion_nombre);
        ProfesorModulo::getConnectionResolver()->setDefaultConnection($conexion_nombre);

        return $proyecto;
    }

    // Método auxiliar para restaurar la conexión masivamente
    private function restoreConnection()
    {
        Modulo::getConnectionResolver()->setDefaultConnection(config('database.default'));
        Alumno::getConnectionResolver()->setDefaultConnection(config('database.default'));
        Ras::getConnectionResolver()->setDefaultConnection(config('database.default'));
        Tarea::getConnectionResolver()->setDefaultConnection(config('database.default'));
        Criterio::getConnectionResolver()->setDefaultConnection(config('database.default'));
        ProfesorModulo::getConnectionResolver()->setDefaultConnection(config('database.default'));
    }

    /**
     * Listado de módulos de un proyecto.
     */
    public function index($proyecto_id)
    {
        $proyecto = $this->setDynamicConnection($proyecto_id);
        $modulos = Modulo::all();

        $this->restoreConnection();
        return view('gestion.modulos.index', compact('modulos', 'proyecto'));
    }

    /**
     * Muestra formulario de creación.
     */
    public function create($proyecto_id)
    {
        $proyecto = $this->setDynamicConnection($proyecto_id);

        $profesores = Profesor::all(); // BD Principal
        $alumnos = Alumno::all(); // BD Proyecto

        $this->restoreConnection();
        return view('gestion.modulos.create', compact('proyecto', 'profesores', 'alumnos'));
    }

    /**
     * Inserta el módulo en la base de datos del proyecto.
     */
    public function store(Request $request, $proyecto_id)
    {
        $proyecto = $this->setDynamicConnection($proyecto_id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:70',
            'unidad' => 'required',
            'profesores' => 'required|array',
            'profesores.*' => 'uuid|exists:mysql.profesores,id_profesor', // Forzamos BD Galileo
            'alumnos' => 'nullable|array',
            'alumnos.*' => 'uuid|exists:alumnos,id_alumno', // Valida en la dinámica
        ]);

        try {
            // Obtenemos el nombre de la conexión que configuramos en setDynamicConnection
            $dynamicConn = Modulo::getConnectionResolver()->getDefaultConnection();

            DB::connection($dynamicConn)->transaction(function () use ($validated, $proyecto) {
                // Al crear el módulo, Eloquent usará la conexión por defecto del Resolver
                $modulo = Modulo::create([
                    'nombre' => $validated['nombre'],
                    'unidad' => $validated['unidad'],
                    'proyecto_id' => $proyecto->id_base_de_datos,
                ]);

                // Las relaciones usarán la lógica de conexión que definimos en el modelo
                $modulo->profesores()->attach($validated['profesores']);

                if (!empty($validated['alumnos'])) {
                    $modulo->alumnos()->attach($validated['alumnos']);
                }
            });

            return redirect()->route('gestion.modulos.index', $proyecto_id)
                ->with('success', 'Módulo creado con éxito.');
        } catch (\Exception $e) {
            // Log::error($e->getMessage()); // Útil para debuggear
            return redirect()->back()->withInput()->withErrors('Error al crear el módulo: ' . $e->getMessage());
        } finally {
            $this->restoreConnection();
        }
    }

    /**
     * Muestra formulario de edición.
     */
    public function edit($proyecto_id, $modulo_id)
    {
        $proyecto = $this->setDynamicConnection($proyecto_id);

        // Extraemos los datos del módulo a mostrar
        $modulo = Modulo::findOrFail($modulo_id);
        $profesores = Profesor::all();
        $alumnos = Alumno::all();

        $alumnos_asignados = $modulo->alumnos->pluck('id_alumno')->toArray(); // Añadimos los alumnos del módulo

        $profesores_asignados = $modulo->profesores->pluck('id_profesor')->toArray(); // Añadimos los profesores del módulo

        $this->restoreConnection();

        return view('gestion.modulos.edit', compact('proyecto', 'modulo', 'profesores', 'alumnos', 'alumnos_asignados', 'profesores_asignados'));
    }

    /**
     * Actualiza los datos y relaciones del módulo.
     */
    public function update(Request $request, $proyecto_id, $modulo_id)
    {
        $this->setDynamicConnection($proyecto_id);
        $modulo = Modulo::findOrFail($modulo_id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:70',
            'unidad' => 'required',
            'profesores' => 'required|array',
            'profesores.*' => 'uuid|exists:mysql.profesores,id_profesor',
            'alumnos' => 'nullable|array',
            'alumnos.*' => 'uuid|exists:alumnos,id_alumno',
        ]);

        try {
            $dynamicConn = Modulo::getConnectionResolver()->getDefaultConnection();

            DB::connection($dynamicConn)->transaction(function () use ($validated, $modulo) {
                $modulo->update([
                    'nombre' => $validated['nombre'],
                    'unidad' => $validated['unidad'],
                ]);

                $modulo->profesores()->sync($validated['profesores']);
                $modulo->alumnos()->sync($validated['alumnos'] ?? []);
            });

            return redirect()->route('gestion.modulos.index', $proyecto_id)
                ->with('success', 'Módulo actualizado con éxito.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->withErrors('Error al actualizar el módulo: ' . $e->getMessage());
        } finally {
            $this->restoreConnection();
        }
    }

    /**
     * Elimina el módulo validando integridad.
     */
    public function destroy($proyecto_id, $modulo_id)
    {
        $this->setDynamicConnection($proyecto_id);
        $modulo = Modulo::findOrFail($modulo_id);

        // 1. Verificar si tiene RAS asociados
        if ($modulo->ras()->exists()) {
            $this->restoreConnection();
            return redirect()->back()->withErrors("No se puede eliminar el módulo **{$modulo->nombre}**. Tiene RAS asociados.");
        }

        // 2. Verificar si hay tareas asociadas
        if (Tarea::where('modulo_id', $modulo->id_modulo)->exists()) {
            $this->restoreConnection();
            return redirect()->back()->withErrors("No se puede eliminar el módulo **{$modulo->nombre}**. Tiene tareas asociadas.");
        }

        try {
            $dynamicConn = Modulo::getConnectionResolver()->getDefaultConnection();

            DB::connection($dynamicConn)->transaction(function () use ($modulo) {
                $modulo->profesores()->detach(); // Importante desvincular también profesores
                $modulo->alumnos()->detach();
                $modulo->delete();
            });
        } catch (\Exception $e) {
            return redirect()->back()->withErrors('Error al eliminar: ' . $e->getMessage());
        } finally {
            $this->restoreConnection();
        }

        return redirect()->route('gestion.modulos.index', $proyecto_id)->with('success', 'Módulo eliminado.');
    }

    /**
     * Importación de RAs y criterios desde csv, xlsx y xls
     */
    public function importarRas(Request $request, $proyecto_id, $modulo_id)
    {
        $request->validate(['archivo_ras' => 'required|mimes:csv,xlsx,xls']);

        try {
            $proyecto = Proyecto::findOrFail($proyecto_id);
            Excel::import(new RasCriteriosImport($proyecto, $modulo_id), $request->file('archivo_ras'));
            return back()->with('success', 'RAs e importación completada.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al importar: ' . $e->getMessage());
        }
    }
}
