<div class="position-relative">
    <select {{ $attributes->merge(['class' => 'form-select form-select-sm text-center select-duracion-ajax']) }}
            style="min-width: 100px;"
            @if($url) data-url="{{ $url }}" @endif
            {{ $disabled ? 'disabled' : '' }}>
        
        <option value="" class="text-muted">-</option>
        
        {{-- $tramos ya viene calculado desde su controlador --}}
        @foreach($tramos as $valor => $etiqueta)
            <option value="{{ $valor }}" {{ $selected == $valor ? 'selected' : '' }}>
                {{ $etiqueta }}
            </option>
        @endforeach
    </select>

    {{-- Indicador de estado para AJAX --}}
    <div class="status-indicator" style="font-size: 0.7rem; height: 15px; position: absolute; width: 100%; left: 0;"></div>
</div>