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
        // Consulta el modelo para obtener los datos necesarios
        $this->databases = Proyecto::all(['proyecto', 'conexion']);
    }

    /**
     * Obtiene la vista/contenido que representa el componente.
     */
    public function render(): View
    {
        return view('components.database-desplegable');
    }
}
