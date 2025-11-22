<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Clase pivote, es necesaria la creación por las bases de datos dinámicas. Esta clase heredará la conexión dinámica del modelo Modulo que lo invoca
 */
class ProfesorModulo extends Pivot
{
    protected $table = 'profesor_modulo'; 
    
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;
}