<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\MorphOne;

use Illuminate\Database\Eloquent\Model;

class Profesor extends Model
{
    use HasUuids;

    /**
     * Define la conexión a la base de datos principal (Galileo). Se usa en los modelos de la base de datos principal
     * @var string
     */
    protected $connection = 'mysql';

    protected $table = "profesores";
    
    protected $primaryKey = 'id_profesor';

    protected $fillable = [
        'nombre',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = true;

    /**
     * Para crear la relación polimórfica
     */
    public function user(): MorphOne
    {
        return $this->morphOne(User::class, 'rolable');
    }
}