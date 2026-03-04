<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\MorphOne;
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
        'periodo',
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
        return $this->belongsToMany(Modulo::class, 
            'alumno_modulo', // Nombre de la tabla pivote
            'alumno_id', // FK de este modelo en la pivote
            'modulo_id',  // FK del modelo relacionado en la pivote
            )->withPivot('deleted_at') // Importante para poder leer el registro borrado
            ->wherePivot('deleted_at', null) // Solo traer los NO borrados por defecto
            ->withTimestamps();
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

    // --- Declaración de Relaciones Polimórficas ---

    /**
     * Obtiene el usuario asociado a este rol de Alumno.
     * Esta es la relación inversa para la vinculación polimórfica (MorphOne).
     */
    public function user(): MorphOne
    {
        // 'rolable' es el UUID de la relación polimórfica en el modelo usuario (el dueño)
        // El segundo argumento es opcional si usas los valores por defecto (rolable_type, rolable_id)
        return $this->morphOne(User::class, 'rolable'); 
    }

    /**
     * Obtiene solo los módulos de los que se ha desmatriculado (Soft Deleted)
     */
    public function modulosBorrados()
    {
        return $this->belongsToMany(Modulo::class, 'alumno_modulo', 'alumno_id', 'modulo_id')
                    ->withPivot('deleted_at')
                    ->wherePivotNotNull('deleted_at') // Solo los que tienen fecha de borrado
                    ->withTimestamps();
    }
}