<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tarea extends Model
{
    use HasUuids, SoftDeletes;

    protected $table = "tareas";
    protected $primaryKey = 'id_tarea';

    protected $fillable = [
        'actividad_id',
        'alumno_id',
        'modulo_id',
        'tarea',
        'notas_alumno',
        'fecha',
        'duracion',
        'apto',
        'calificacion',
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

    // Relaciones
    public function actividad(): BelongsTo
    {
        return $this->belongsTo(Actividad::class, 'actividad_id', 'id_actividad');
    }

    public function alumno(): BelongsTo
    {
        return $this->belongsTo(Alumno::class, 'alumno_id', 'id_alumno');
    }

    public function modulo(): BelongsTo
    {
        return $this->belongsTo(Modulo::class, 'modulo_id', 'id_modulo');
    }
}