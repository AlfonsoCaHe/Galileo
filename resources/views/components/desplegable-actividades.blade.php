<div class="form-group">
    <label for="actividad_id">Selecciona la Actividad:</label>
    <select name="actividad_id" id="actividad_id" class="form-control" required>
        <option value="">-- Selecciona una actividad --</option>
        @foreach($modulos as $modulo)
            @foreach($modulo->actividades as $actividad)
                <option value="{{ $actividad->id_actividad }}">
                    {{ $actividad->tarea }} 
                </option>
            @endforeach
        @endforeach
    </select>
</div>