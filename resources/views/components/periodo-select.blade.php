<div class="d-flex align-items-center gap-2">
    <select {{ $attributes->merge(['class' => 'mt-1 block w-full rounded-md border-gray-300 shadow-sm form-select form-select-sm select-periodo']) }}>
        <option value="">-- Periodo --</option>
        @foreach($periodos as $periodo)
            <option value="{{ $periodo }}" {{ $periodo == $selected ? 'selected' : '' }}>
                {{ $periodo }}
            </option>
        @endforeach
    </select>
    <span class="status-indicator" style="min-width: 20px;"></span>
</div>