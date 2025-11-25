<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Criterio extends Model
{
    use HasUuids;

    protected $table = "criterios";
    
    protected $primaryKey = 'id_criterio';

    protected $fillable = [
        'nombre',
        'descripcion',
        'ras_id'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = true;

    /**
     * Obtiene el ras asociado al criterio (FK: ras_id).
     */
    public function ras(): BelongsTo
    {
        return $this->belongsTo(Ras::class, 'ras_id', 'id_ras');
    }
}