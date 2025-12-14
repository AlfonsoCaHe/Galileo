<?php

namespace App\View\Components;

use Illuminate\View\Component;

class PeriodoSelect extends Component
{
    public $periodos;
    public $selected;

    public function __construct($selected = null)
    {
        // Definimos las opciones que coinciden con el ENUM de la base de datos
        $this->periodos = ['Periodo 1', 'Periodo 2'];
        $this->selected = $selected;
    }

    public function render()
    {
        return view('components.periodo-select');
    }
}