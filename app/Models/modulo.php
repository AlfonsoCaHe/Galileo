<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Modulo extends Model
{
    use HasUuids;

    //protected $connection = 'mysql';

    protected $table = "modulos";
    
    protected $primaryKey = 'id_modulo';

    protected $fillable = [
        'nombre',
        'profesor_id'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = true;

    //-- Declaración de relaciones

    /**
     * Obtiene el profesor asociado al módulo.
     * La consulta debe forzarse a la BD principal (Galileo).
     */
    public function profesor(): BelongsTo{
        return $this->belongsTo(Profesor::class, 'profesor_id', 'id_profesor')
                    ->on('mysql'); // O .on('galileo')
    }

    /**
     * Obtiene los alumnos del modulo (Tabla Pivote: alumnos_modulos).
     */
    public function alumnos(): BelongsToMany
    {
        return $this->belongsToMany(
            Alumno::class, 
            'alumnos_modulos', 
            'modulo_id',       // 1. FK de este modelo (Modulo) en la tabla pivote
            'alumno_id',       // 2. FK del modelo relacionado (Alumno) en la tabla pivote
            'id_modulo',       // 3. Clave local (PK de Modulo)
            'id_alumno'        // 4. Clave del modelo relacionado (PK de Alumno)
        );
    }

    /**
     * Obtiene los ras del modulo (Tabla Pivote: modulos_ras).
     */
    public function ras(): BelongsToMany
    {
        return $this->belongsToMany(
            Ras::class, 
            'modulo_ras',    // Nombre de la tabla pivot
            'modulo_id',     // FK de este modelo en la pivot
            'ras_id'         // FK del modelo relacionado en la pivot
        );  
    }
}