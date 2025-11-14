<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TutorLaboral extends Model
{
    use HasUuids;

    protected $table = "tutores_laborales";
    
    protected $primaryKey = 'id_tutor_laboral';

    protected $fillable = [
        'nombre',
        'empresa_id'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = true;

    //-- Declaración de relaciones --

    /**
     * Obtiene la empresa asociada al tutor_laboral (FK: empresa_id).
     */
    public function tutorLaboral(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id', 'id_empresa');
    }
}