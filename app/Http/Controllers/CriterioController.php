<?php

namespace App\Http\Controllers;

use App\Models\Proyecto;
use App\Models\Ras;
use App\Models\Criterio;
use App\Rules\ValidarTexto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class CriterioController extends Controller
{
    private function setDynamicConnection($proyecto_id)
    {
        $proyecto = Proyecto::findOrFail($proyecto_id);
        $connectionName = 'dynamic_' . $proyecto->id_base_de_datos;
        
        $config = config('database.connections.mysql');
        $config['database'] = $proyecto->conexion;
        Config::set("database.connections.{$connectionName}", $config);
        DB::purge($connectionName);

        Ras::getConnectionResolver()->setDefaultConnection($connectionName);
        Criterio::getConnectionResolver()->setDefaultConnection($connectionName);
    }

    /**
     * Guarda un Criterio asociado a un RA específico.
     */
    public function store(Request $request, $proyecto_id, $ra_id)
    {
        $validated = $request->validate([
            'ce' => ['required', 'string', 'max:10'], // Ej: "1.a", "2.b"
            'descripcion' => ['required', 'string', 'max:250'],
        ]);

        $this->setDynamicConnection($proyecto_id);

        try {
            Criterio::create([
                'ce' => $validated['ce'],
                'descripcion' => $validated['descripcion'],
                'ras_id' => $ra_id
            ]);

            // Redirigimos con una variable de sesión para re-abrir el acordeón automáticamente (Truco de UX)
            return redirect()->back()
                ->with('success', 'Criterio añadido correctamente.')
                ->with('open_ra', $ra_id); // Enviamos el ID del RA para que JS lo abra

        } catch (\Exception $e) {
            return redirect()->back()->withErrors('Error al crear criterio: ' . $e->getMessage());
        }
    }

    public function destroy($proyecto_id, $criterio_id)
    {
        $this->setDynamicConnection($proyecto_id);

        try {
            $criterio = Criterio::findOrFail($criterio_id);
            $ra_id = $criterio->ras_id; // Guardamos el ID para reabrir el acordeón
            $criterio->delete();

            return redirect()->back()
                ->with('success', 'Criterio eliminado.')
                ->with('open_ra', $ra_id);

        } catch (\Exception $e) {
            return redirect()->back()->withErrors('Error al eliminar criterio: ' . $e->getMessage());
        }
    }
}