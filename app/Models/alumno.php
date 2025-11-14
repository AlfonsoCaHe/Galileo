<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

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
        return $this->belongsTo(Profesor::class, 'tutor_docente_id', 'id_profesor')
                    ->on('mysql'); // O .on('galileo') si así se llama tu conexión principal
    }

    /**
     * Obtiene los módulos del alumno (Tabla Pivote: alumnos_modulos).
     */
    public function modulos(): BelongsToMany
    {
        return $this->belongsToMany(
            Modulo::class, 
            'alumnos_modulos', // Nombre de la tabla pivote
            'alumno_id',       // FK de este modelo en la pivote
            'modulo_id'        // FK del modelo relacionado en la pivote
        )->withTimestamps(); // Añadir si la tabla pivote tiene timestamps
    }
}