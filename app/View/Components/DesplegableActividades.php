<?php

namespace App\View\Components;

use App\Models\Proyecto;
use Illuminate\View\Component;
use Illuminate\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class DesplegableActividades extends Component
{
    public Collection $modulos;

    /**
     * Crea una nueva instancia del componente.
     */
    public function __construct($proyectoId)
    {
        $userAlumno = Auth::user()->rolable_id; // Obtenemos el alumno actual
        $proyecto = Proyecto::where('id_base_de_datos', $proyectoId)->firstOrFail();

        // 1. Establecemos la conexión dinámica
        $connectionName = 'dynamic_' . $proyectoId;
        
        $config = config('database.connections.mysql'); 
        $config['database'] = $proyecto->conexion;
        Config::set("database.connections.{$connectionName}", $config);
        DB::purge($connectionName); // Importante purgar para asegurar cambio

        // 2. Obtenemos Módulos (filtrados por alumno) Y sus Actividades en una sola estructura
        // Usamos la tabla pivote alumno_modulo para filtrar
        $modulos = DB::connection($connectionName)
            ->table('modulos')
            ->join('alumno_modulo', 'modulos.id_modulo', '=', 'alumno_modulo.modulo_id')
            ->where('alumno_modulo.alumno_id', $userAlumno)
            ->whereNull('alumno_modulo.deleted_at') // Solo matriculas activas
            ->select('modulos.id_modulo', 'modulos.nombre')
            ->get();

        // 3. Inyectamos las actividades a cada módulo
        // Hacemos esto para mantener la estructura de agrupación en la vista
        $modulos->map(function ($modulo) use ($connectionName) {
            $modulo->actividades = DB::connection($connectionName)
                ->table('actividades')
                ->where('modulo_id', $modulo->id_modulo)
                ->select('id_actividad', 'tarea', 'descripcion') // Seleccionamos solo lo necesario
                ->get();
            
            return $modulo;
        });
        
        // Filtramos para mostrar solo módulos que tengan al menos una actividad creada
        $this->modulos = $modulos->filter(function($modulo) {
            return $modulo->actividades->isNotEmpty();
        });
    }

    public function render(): View
    {
        return view('components.desplegable-actividades');
    }
}