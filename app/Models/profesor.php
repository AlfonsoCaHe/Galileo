<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

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
}