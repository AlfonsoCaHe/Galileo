<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Relations\MorphOne;

use Illuminate\Database\Eloquent\Model;

class Profesor extends Model
{
    use HasUuids;

    protected $table = "profesores";
    
    protected $primaryKey = 'id_profesor';

    protected $fillable = [
        'nombre',
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = true;

    /**
     * Define la conexión a la base de datos principal (Galileo).
     * Asumiendo que 'mysql' es tu conexión principal por defecto.
     * @var string
     */
    protected $connection = 'mysql';

    /**
     * Para crear la relación polimórfica
     */
    public function user(): MorphOne
    {
        return $this->morphOne(User::class, 'rolable');
    }
}