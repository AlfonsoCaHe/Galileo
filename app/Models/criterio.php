<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;

class Criterio extends Model
{
    use HasUuids;

    protected $table = "criterios";
    
    protected $primaryKey = 'id_criterio';

    protected $fillable = [
        'nombre',
        'descripcion'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = true;
}