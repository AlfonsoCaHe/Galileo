<?php

namespace App\Http\Controllers;

use App\Models\Modulo;
use App\Models\Proyecto;
use App\Models\Profesor; // Desde BD principal
use App\Models\Alumno;   // Desde BD dinámica
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModuloController extends Controller
{
    // Método auxiliar para configurar la conexión dinámica
    private function setDynamicConnection($proyecto_id)
    {
        $proyecto = Proyecto::findOrFail($proyecto_id);
        $conexion_nombre = 'proyecto_temp_' . $proyecto->id_base_de_datos;
        $config_base = config('database.connections.' . config('database.default'));
        
        $config_base['database'] = $proyecto->conexion;
        config(["database.connections.{$conexion_nombre}" => $config_base]);

        // Forzamos a los modelos dinámicos a usar esta conexión
        Modulo::getConnectionResolver()->setDefaultConnection($conexion_nombre);
        Alumno::getConnectionResolver()->setDefaultConnection($conexion_nombre);
        // [Añadir otros modelos locales: Ras::class, Tarea::class, etc.]
        
        return $proyecto;
    }
    
    // Método auxiliar para restaurar la conexión
    private function restoreConnection()
    {
        // Restaurar la conexión predeterminada (Galileo)
        Modulo::getConnectionResolver()->setDefaultConnection(config('database.default'));
        Alumno::getConnectionResolver()->setDefaultConnection(config('database.default'));
        // [Añadir otros modelos locales]
    }

    /**
     * Método que redirige al listado de módulos de un proyecto
     */
    public function index($proyecto_id)
    {
        $proyecto = $this->setDynamicConnection($proyecto_id);
        $modulos = Modulo::all();
        $this->restoreConnection();
        return view('gestion.modulos.index', compact('modulos', 'proyecto'));
    }

    /**
     * Método que redirige a la vista que gestiona la creación de un módulo
     */
    public function create($proyecto_id)
    {
        $proyecto = $this->setDynamicConnection($proyecto_id);
        
        // Profesores de la BD principal
        $profesores = Profesor::all(); 
        
        // Alumnos del proyecto actual
        $alumnos = Alumno::all(); 
        
        $this->restoreConnection();

        return view('gestion.modulos.create', compact('proyecto', 'profesores', 'alumnos'));
    }

    /**
     * Método que inserta el módulo en la base de datos
     */
    public function store(Request $request, $proyecto_id)
    {
        $proyecto = $this->setDynamicConnection($proyecto_id);

        $validated = $request->validate([
            'nombre' => 'required|string|max:70',
            'profesores' => 'required|array', 
            'profesores.*' => 'uuid|exists:mysql.profesores,id_profesor', // Validamos que los UUIDs existan en la tabla profesores de la conexión 'mysql'
            'alumnos' => 'nullable|array',
            'alumnos.*' => 'uuid|exists:alumnos,id_alumno', // Validamos que los UUIDs existan en la tabla alumnos de la conexión dinámica
        ]);

        try {
            DB::connection((new Modulo())->getConnectionName())->transaction(function () use ($validated, $proyecto) {
                
                // 1. Crear el Módulo (sin profesor_id)
                $modulo = Modulo::create([
                    'nombre' => $validated['nombre'],
                    'proyecto_id' => $proyecto->id_base_de_datos, 
                ]);
                // 2. Asociar Profesores (Many-to-Many)
                $modulo->profesores()->attach($validated['profesores']);
                
                // 3. Asociar Alumnos (Many-to-Many)
                if (isset($validated['alumnos'])) {
                    $modulo->alumnos()->attach($validated['alumnos']);
                }
            });

            return redirect()->route('gestion.modulos.index', ['proyecto_id' => $proyecto_id])
                            ->with('success', 'Módulo creado con éxito.');

        } catch (\Exception $e) {
            return redirect()->back()->withInput()->withErrors('Error al crear el módulo: ' . $e->getMessage());
        }
    }

    /**
     * Método que redirige a la vista de edición del módulo del proyecto en cuestión
     */
    public function edit($proyecto_id, $modulo_id)
    {
        $proyecto = $this->setDynamicConnection($proyecto_id);
        
        $modulo = Modulo::findOrFail($modulo_id);
        $profesores = Profesor::all();
        $alumnos = Alumno::all();
        
        $alumnos_asignados = $modulo->alumnos->pluck('id_alumno')->toArray();// Añadimos los alumnos del módulo
        
        $profesores_asignados = $modulo->profesores->pluck('id_profesor')->toArray(); // Añadimos los profesores del módulo

        $this->restoreConnection();

        return view('gestion.modulos.edit', compact('proyecto', 'modulo', 'profesores', 'alumnos', 'alumnos_asignados', 'profesores_asignados'));
    }
    
    /**
     * Método para almacenar los cambios en el módulo
     */
    public function update(Request $request, $proyecto_id, $modulo_id)
    {
        $proyecto = $this->setDynamicConnection($proyecto_id);
        $modulo = Modulo::findOrFail($modulo_id);
        
        $validated = $request->validate([
            'nombre' => 'required|string|max:70',
            'profesores' => 'required|array',
            'profesores.*' => 'uuid|exists:mysql.profesores,id_profesor', // Validamos que los UUIDs existan en la tabla profesores de la conexión 'mysql'
            'alumnos' => 'nullable|array',
            'alumnos.*' => 'uuid|exists:alumnos,id_alumno', // Validamos que los UUIDs existan en la tabla alumnos de la conexión dinámica
        ]);

        try {
            DB::connection($modulo->getConnectionName())->transaction(function () use ($validated, $modulo) {
                
                // 1. Actualizar datos del Módulo (sin profesor_id)
                $modulo->update([
                    'nombre' => $validated['nombre'],
                ]);

                // 2. Sincronizar Profesores (añadir/eliminar relaciones)
                $modulo->profesores()->sync($validated['profesores']);
                
                // 3. Sincronizar Alumnos
                $modulo->alumnos()->sync($validated['alumnos'] ?? []);
            });

            return redirect()->route('gestion.modulos.index', ['proyecto_id' => $proyecto_id])
                            ->with('success', 'Módulo actualizado con éxito.');

        } catch (\Exception $e) {
            return redirect()->back()->withInput()->withErrors('Error al actualizar el módulo: ' . $e->getMessage());
        }
    }

    /**
     * Método para eliminar un módulo del proyecto. No se puede eliminar si tiene RAs o Tareas asociadas
     * Con los alumnos solo rompe el enlace
     */
    public function destroy($proyecto_id, $modulo_id)
    {
        $proyecto = $this->setDynamicConnection($proyecto_id);
        $modulo = Modulo::findOrFail($modulo_id);
        
        // Comprobamos la integridad referencial de los datos
        // 1. Verificar si tiene RAS asociados
        if ($modulo->ras()->count() > 0) {
            $this->restoreConnection();
            return redirect()->back()->withErrors('No se puede eliminar el módulo **' . $modulo->nombre . '**. Tiene Resultados de Aprendizaje (RAS) asociados. Elimínalos primero.');
        }

        // 2. Verificar si hay tareas asociadas a este módulo
        if (\App\Models\Tarea::on(Modulo::getConnectionName())->where('modulo_id', $modulo->id_modulo)->count() > 0) {
             $this->restoreConnection();
             return redirect()->back()->withErrors('No se puede eliminar el módulo **' . $modulo->nombre . '**. Tiene tareas asociadas. Elimínalas o desvinculalas primero.');
        }

        try {
            DB::connection(Modulo::getConnectionName())->transaction(function () use ($modulo) {
                // Desvinculamos los alumnos (borra los registros de la tabla pivote)
                $modulo->alumnos()->detach(); 
                
                // Eliminamos el módulo
                $modulo->delete();
            });

        } catch (\Exception $e) {
            $this->restoreConnection();
            return redirect()->back()->withErrors('Error al eliminar el módulo: ' . $e->getMessage());
        }

        $this->restoreConnection();
        return redirect()->route('gestion.modulos.index', $proyecto_id)
                         ->with('success', 'Módulo eliminado con éxito.');
    }
}