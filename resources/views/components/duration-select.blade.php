@props(['selected' => null, 'url' => null, 'disabled' => false])

@php
    // Generación dinámica de tramos de 30 min (00:30 a 08:00)
    $tramos = [];
    $inicio = 30; // 30 minutos
    $fin = 480;   // 8 horas
    
    for ($i = $inicio; $i <= $fin; $i += 30) {
        $horas = floor($i / 60);
        $minutos = $i % 60;
        // Formato valor: "01:30"
        $valor = sprintf('%02d:%02d', $horas, $minutos);
        // Formato etiqueta: "1h 30m"
        $etiqueta = $horas . ':' . ($minutos > 0 ? $minutos : '00'); 
        $tramos[$valor] = $etiqueta;
    }
@endphp

<div class="position-relative">
    <select {{ $attributes->merge(['class' => 'form-select form-select-sm text-center select-duracion-ajax']) }}
            style="min-width: 100px;"
            data-url="{{ $url }}"
            {{ $disabled ? 'disabled' : '' }}>
        
        <option value="" class="text-muted">-</option>
        
        @foreach($tramos as $valor => $etiqueta)
            <option value="{{ $valor }}" {{ $selected == $valor ? 'selected' : '' }}>
                {{ $etiqueta }}
            </option>
        @endforeach
    </select>

    {{-- Indicador de estado (Guardando/Guardado) --}}
    <div class="status-indicator" style="font-size: 0.7rem; height: 15px; position: absolute; width: 100%; left: 0;"></div>
</div>