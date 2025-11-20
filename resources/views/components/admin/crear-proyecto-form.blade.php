@props(['yearStart' => now()->year])

<form method="POST" action="{{ route('admin.crear.proyecto') }}">
    @csrf
    
    <div class="mb-3">
        <label for="year_start" class="form-label">Año de Inicio del Proyecto:</label>
        {{-- Campo oculto para el año, si no quieres mostrarlo --}}
        {{-- Si lo quieres visible, cámbialo a type="number" --}}
        <input type="number" id="year_start" name="year_start" value="{{ $yearStart }}" class="form-control w-auto"> 
    </div>
    
    <button type="submit" class="btn btn-primary" onclick="this.disabled=true; this.innerText='Procesando...'; this.form.submit();">
        Crear Nueva Base de Datos de Proyecto
    </button>
</form>