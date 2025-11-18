@props(['profesorId', 'currentFiltro'])

<form method="GET" action="{{ route('profesor.alumnos', $profesorId) }}" class="d-flex align-items-center">
    <label for="filtro-select" class="me-2 text-muted">Mostrar alumnos:</label>
    <select name="filtro" id="filtro-select" class="form-select w-auto" onchange="this.form.submit()">
        <option value="docente" @selected($currentFiltro == 'docente')>
            Tutor Docente
        </option>
        <option value="todos" @selected($currentFiltro == 'todos')>
            Profesor de Módulos
        </option>
    </select>
</form>