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

    public function modulos()
    {
        // Asumo que tienes una tabla intermedia o una relación directa. 
        // Si usas una tabla pivote (Profesor <-> Modulo):
        return $this->belongsToMany(Modulo::class, 'profesor_modulo', 'profesor_id', 'modulo_id');
        
        // O SI el módulo tiene directamente el 'profesor_id':
        // return $this->hasMany(Modulo::class, 'profesor_id');
    }
}