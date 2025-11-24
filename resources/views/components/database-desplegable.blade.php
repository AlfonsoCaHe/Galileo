<div class="form-group mb-2">
    <label for="database-select" class="fw-semibold">Seleccionar Proyecto</label>
    
    <select name="database_id" id="database-select" class="form-control">
        <option value="">Elige un Proyecto</option>
        
        @foreach ($databases as $db)
            {{--Usamos la 'conexion' como valor y el 'proyecto' como texto visible--}}
            <option value="{{ $db->id_base_de_datos }}"> 
                {{ $db->proyecto }}
            </option>
        @endforeach
    </select>
    
    @if ($databases->isEmpty())
        <strong class="text-danger" style="color:red;">Aviso: No se encontraron bases de datos.</strong>
    @endif
</div>