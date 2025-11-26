<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\Modulo;
use App\Models\Ras;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class RaController extends Controller
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

        // Forzamos la conexión en los modelos
        Modulo::getConnectionResolver()->setDefaultConnection($connectionName);
        Ras::getConnectionResolver()->setDefaultConnection($connectionName);
        
        return $connectionName;
    }

    /**
     * Muestra el listado de RAs y Criterios en formato acordeón.
     */
    public function index($proyecto_id, $modulo_id)
    {
        $this->setDynamicConnection($proyecto_id);

        // Cargamos el módulo con sus RAs y los Criterios de esos RAs (Eager Loading anidado)
        $modulo = Modulo::with('ras.criterios')->findOrFail($modulo_id);

        return view('gestion.ras.index', compact('proyecto_id', 'modulo'));
    }

    /**
     * Guarda un nuevo Resultado de Aprendizaje (RA).
     */
    public function store(Request $request, $proyecto_id, $modulo_id)
    {
        // Validamos código y descripción por separado
        $validated = $request->validate([
            'codigo' => ['required', 'string', 'max:20'], // Ej: RA1
            'descripcion' => ['required', 'string', 'max:250'], 
        ]);

        $this->setDynamicConnection($proyecto_id);

        try {
            Ras::create([
                'codigo' => $validated['codigo'],        // Guardamos RA1
                'descripcion' => $validated['descripcion'], // Guardamos la definición
                'modulo_id' => $modulo_id
            ]);

            return redirect()->back()->with('success', 'Resultado de Aprendizaje creado correctamente.');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors('Error al crear RA: ' . $e->getMessage());
        }
    }

    /**
     * Elimina un RA (y por cascada sus criterios).
     */
    public function destroy($proyecto_id, $ra_id)
    {
        $this->setDynamicConnection($proyecto_id);

        try {
            $ra = Ras::findOrFail($ra_id);
            $ra->delete();

            return redirect()->back()->with('success', 'Resultado de Aprendizaje eliminado.');

        } catch (\Exception $e) {
            return redirect()->back()->withErrors('Error al eliminar RA: ' . $e->getMessage());
        }
    }
    
    // De momento dejamos edit/update pendientes o los implementamos inline si prefieres después.
}