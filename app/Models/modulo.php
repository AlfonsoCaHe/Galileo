<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use App\Models\ProfesorModulo;

class Modulo extends Model
{
    use HasUuids;

    protected $table = "modulos";
    protected $primaryKey = 'id_modulo';
    protected $fillable = [
        'nombre',
        'unidad', //Unidad al que pertenece el módulo
        'proyecto_id'  // Identificador del proyecto en la BD Galileo
    ];
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    // --- Relaciones ---

    // Relación con el profesor que está en la BD Galileo
    public function profesor(): BelongsTo
    {
        return $this->belongsTo(Profesor::class, 'profesor_id', 'id_profesor');
    }

    // Relación con los Resultados de Aprendizaje (RAS) - Local
    public function ras(): HasMany
    {
        // Usará la conexión dinámica actual
        return $this->hasMany(Ras::class, 'modulo_id', 'id_modulo');
    }
    
    // Relación con los alumnos (Muchos a muchos) - Local
    public function alumnos(): BelongsToMany
    {
        return $this->belongsToMany(
            Alumno::class, 
            'alumno_modulo',   // Tabla pivote
            'modulo_id',       // FK de este modelo
            'alumno_id'        // FK del modelo relacionado
        )->withPivot('deleted_at')
        ->wherePivot('deleted_at', null)
        ->withTimestamps();;
    }

    /**
     * Relación con los profesores (Muchos a Muchos). La tabla pivote ('profesor_modulo') está en la BD dinámica actual.
     */
    public function profesores(): BelongsToMany
    {
        $dynamicConnectionName = $this->getConnectionName();
        $dbName = config("database.connections.{$dynamicConnectionName}.database");

        // Validación de seguridad
        if (empty($dbName)) {
            throw new \Exception("No se ha podido determinar la base de datos para la conexión: {$dynamicConnectionName}");
        }

        $fullyQualifiedPivotTable = "{$dbName}.profesor_modulo";
        
        return $this->belongsToMany(
            Profesor::class,
            $fullyQualifiedPivotTable,
            'modulo_id',
            'profesor_id'
        )->using(ProfesorModulo::class);
    }
}