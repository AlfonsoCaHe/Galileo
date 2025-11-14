<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Proyecto extends Model
{
    use HasUuids;

    protected $table = "bases_de_datos";
    
    protected $primaryKey = 'id_proyecto';

    protected $fillable = [
        'proyecto',
        'conexion'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = true;
}