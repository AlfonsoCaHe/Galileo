<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;

class TutorLaboral extends Model
{
    use HasUuids;

    protected $connection = 'mysql';

    protected $table = "tutores_laborales";
    
    protected $primaryKey = 'id_tutor_laboral';

    protected $fillable = [
        'nombre',
        'email',
        'empresa_id'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = true;

    //-- Declaración de relaciones --

    /**
     * Define la relación polimórfica inversa con el modelo User.
     */
    public function user(): MorphOne
    {
        // 'rolable' indica a Laravel que use las columnas 'rolable_id' y 'rolable_type' en la tabla users
        return $this->morphOne(User::class, 'rolable');
    }

    /**
     * Obtiene la empresa asociada al tutor_laboral (FK: empresa_id).
     */
    public function Empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class, 'empresa_id', 'id_empresa');
    }
}