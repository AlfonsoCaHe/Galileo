<?php

namespace App\View\Components;

use App\Models\Proyecto;
use Illuminate\View\Component;
use Illuminate\View\View;
use Illuminate\Support\Collection;

class DatabaseDesplegable extends Component
{
    public Collection $databases;

    /**
     * Crea una nueva instancia del componente.
     */
    public function __construct()
    {
        $this->databases = Proyecto::where('finalizado', 0)->get(['proyecto', 'conexion', 'id_base_de_datos']);
    }

    /**
     * Obtiene la vista/contenido que representa el componente.
     */
    public function render(): View
    {
        return view('components.database-desplegable');
    }
}
