<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tarea extends Model
{
    use HasUuids;

    protected $table = "tareas";
    protected $primaryKey = 'id_tarea';

    protected $fillable = [
        'nombre',
        'tarea',//Desplegable para los alumnos
        'descripcion',
        'notas_alumno',
        'fecha',
        'duracion',
        'modulo_id',
        'alumno_id',
        'apto',
        'bloqueado'
    ];

    protected $casts = [
        'apto' => 'boolean',
        'bloqueado' => 'boolean',
        'fecha' => 'date'
    ];

    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class, 'alumno_id', 'id_alumno');
    }

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(Modulo::class, 'modulo_id', 'id_modulo');
    }

    public function criterios(): BelongsToMany
    {
        return $this->belongsToMany(Criterio::class, 'tareas_criterios', 'tarea_id', 'criterio_id');
    }
}