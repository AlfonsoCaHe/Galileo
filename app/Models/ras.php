<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ras extends Model
{
    use HasUuids;

    protected $table = 'ras';
    
    protected $primaryKey = 'id_ras';

    protected $fillable = [
        'nombre',
        'modulo_id' // Clave foránea para la relación 1:N con Modulo
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = true;

    // --- Declaración de Relaciones ---

    /**
     * Obtiene el Módulo al que pertenece este Resultado de Aprendizaje (RAS).
     * (Relación 1:N inversa)
     */
    public function modulo(): BelongsTo
    {
        return $this->belongsTo(Modulo::class, 'modulo_id', 'id_modulo'); 
    }

    /**
     * Obtiene todos los Criterios de Evaluación asociados a este RAS.
     * (Relación 1:N)
     */
    public function criterios(): HasMany
    {
        return $this->hasMany(Criterio::class, 'ras_id', 'id_ras');
    }
}