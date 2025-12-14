<?php

namespace App\View\Components;

use Illuminate\View\Component;

class DurationSelect extends Component
{
    public $selected;
    public $url;
    public $disabled;
    public $tramos;

    /**
     * Create a new component instance.
     */
    public function __construct($selected = null, $url = null, $disabled = false)
    {
        $this->selected = $selected;
        $this->url = $url;
        $this->disabled = $disabled;
        
        // Creamos los tramos horarios, así es más fácil modificarlos si se cambia el número de horas de las tareas
        $this->tramos = $this->generarTramos();
    }

    /**
     * Para crear el contenido de las horas del componente
     */
    private function generarTramos(): array
    {
        $tramos = [];
        $inicio = 30; // 30 minutos
        $fin = 480; // 8 horas
        
        for ($i = $inicio; $i <= $fin; $i += 30) {
            $horas = floor($i / 60);
            $minutos = $i % 60;
            
            // Formato valor para: "01:30"
            $valor = sprintf('%02d:%02d', $horas, $minutos);
            
            // Formato etiqueta humana: "1:30" o "2:00"
            $etiqueta = $horas . ':' . ($minutos > 0 ? str_pad($minutos, 2, '0', STR_PAD_LEFT) : '00'); 
            
            $tramos[$valor] = $etiqueta;
        }

        return $tramos;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.duration-select');
    }
}