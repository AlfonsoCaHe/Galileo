<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;
use App\Models\TutorLaboral;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    use HasUuids;

    protected $connection = 'mysql';

    protected $table = "empresas";
    
    protected $primaryKey = 'id_empresa';

    protected $fillable = [
        'cif_nif',
        'nombre',
        'nombre_gerente',
        'nif_gerente'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = true;

    /**
     * Obtiene todos los tutores laborales asociados a esta empresa.
     */
    public function tutores(): HasMany
    {
        // Asumiendo que la FK es 'empresa_id' en la tabla 'tutores_laborales'
        return $this->hasMany(TutorLaboral::class, 'empresa_id', 'id_empresa');
    }
}