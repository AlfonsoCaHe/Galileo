<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Actividad extends Model
{
    use HasUuids;

    protected $table = 'actividades';
    protected $primaryKey = 'id_actividad';

    protected $fillable = [
        'nombre',
        'tarea',// Contenido del desplegable
        'descripcion',
        'modulo_id'
    ];

    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    /**
     * Relación: La actividad pertenece a un módulo.
     */
    public function modulo(): BelongsTo
    {
        return $this->belongsTo(Modulo::class, 'modulo_id', 'id_modulo');
    }

    /**
     * Relación: Una actividad tiene muchas tareas realizadas por diferentes alumnos.
     */
    public function tareas(): HasMany
    {
        return $this->hasMany(Tarea::class, 'actividad_id', 'id_actividad');
    }

    /**
     * Relación N:M
     * Define qué criterios se evalúan en esta actividad.
     */
    public function criterios(): BelongsToMany
    {
        return $this->belongsToMany(Criterio::class, 'actividad_criterio', 'actividad_id', 'criterio_id');
    }
}