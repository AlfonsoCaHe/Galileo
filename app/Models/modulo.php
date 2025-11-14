<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Modulo extends Model
{
    use HasUuids;

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
     * Obtiene el profesor asociado al modulo (FK: modulo_id).
     */
    public function profesor(): BelongsTo{
        return $this->belongsTo(Profesor::class, 'profesor_id', 'id_profesor');
    }

    /**
     * Obtiene los alumnos del modulo (Tabla Pivote: alumnos_modulos).
     */
    public function alumnos(): BelongsToMany
    {
        return $this->belongsToMany(
            Alumno::class, 
            'alumnos_modulos', 
            'modulo_id',       
            'alumno_id'        
        )->withTimestamps();
    }
}