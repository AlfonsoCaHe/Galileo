<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;

class Ras extends Model
{
    use HasUuids;

    protected $table = "ras";
    
    protected $primaryKey = 'id_ras';

    protected $fillable = [
        'nombre'
    ];

    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = true;
}