<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Alumno extends Model
{
    use HasUuids;

    protected $table = "alumnos";
    
    protected $primaryKey = 'id_alumno';

    protected $fillable = [
        'nombre',
        'tutor_laboral_id',
        'tutor_docente_id'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = true;

    // --- Declaración de Relaciones ---

    /**
     * Obtiene el tutor laboral asociado al alumno (FK: tutor_laboral_id).
     */
    public function tutorLaboral(): BelongsTo
    {
        return $this->belongsTo(TutorLaboral::class, 'tutor_laboral_id', 'id_tutor_laboral'); 
    }

    /**
     * Obtiene el tutor docente (que siempre será un Profesor) asociado al alumno.
     * La consulta debe forzarse a la BD principal (Galileo).
     */
    public function tutorDocente(): BelongsTo
    {
        return $this->belongsTo(Profesor::class, 'tutor_docente_id', 'id_profesor');
    }

    /**
     * Obtiene los módulos del alumno (Tabla Pivote: alumnos_modulos).
     */
    public function modulos(): BelongsToMany
    {
        // return $this->belongsToMany(
        //     Modulo::class, 
        //     'alumnos_modulos', // Nombre de la tabla pivote
        //     'alumno_id',       // FK de este modelo en la pivote
        //     'modulo_id'        // FK del modelo relacionado en la pivote
        // )->withTimestamps(); // Añadir si la tabla pivote tiene timestamps

        return $this->belongsToMany(
            Modulo::class, 
            'alumnos_modulos', 
            'alumno_id',       // 1. FK de este modelo (Alumno) en la tabla pivote
            'modulo_id',       // 2. FK del modelo relacionado (Modulo) en la tabla pivote
            'id_alumno',       // 3. Clave local (PK de Alumno)
            'id_modulo'        // 4. Clave del modelo relacionado (PK de Modulo)
        );  
    }

    /**
     * Obtiene las tareas asignadas al alumno (FK: alumno_id).
     * Esta relación usa la conexión dinámica asignada.
     */
    public function tareas(): HasMany
    {
        // 'alumno_id' es la FK en la tabla 'tareas' que apunta a 'id_alumno' en 'alumnos'
        return $this->hasMany(Tarea::class, 'alumno_id', 'id_alumno'); 
    }
}