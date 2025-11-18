<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

use Illuminate\Database\Eloquent\Model;

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
}