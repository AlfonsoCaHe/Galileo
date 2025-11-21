<div class="form-group d-flex w-100">
    <label for="rol-select" class="form-label fw-semibold w-50">Selecciona un rol: </label>
    
    <select name="rol" id="rol-select" class="form-control w-50">
        <option value="">Elige un Rol</option>
            <option value="{{ 'alumno' }}">
                Alumno
            </option>
            <option value="{{ 'profesor' }}">
                Profesor
            </option>
            <option value="{{ 'tutor_laboral' }}">
                Tutor Laboral
            </option>
    </select>
</div>