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
        'proyecto_id'  // Identificador del proyecto (BD Galileo)
    ];
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = true;

    // --- Relaciones ---

    // Relación con el profesor que está en la BD principal (Galileo)
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
            'alumnos_modulos', // Tabla pivote
            'modulo_id',       // FK de este modelo
            'alumno_id'        // FK del modelo relacionado
        );
    }

    /**
     * Relación con los profesores (Muchos a Muchos). La tabla pivote ('profesor_modulo') está en la BD dinámica actual.
     */
    public function profesores(): BelongsToMany
    {
        // 1. Obtener el nombre de la conexión temporal configurada (e.g., 'proyecto_temp_...')
        $dynamicConnectionName = $this->getConnectionName();
        
        // 2. OBTENER EL NOMBRE REAL DE LA BASE DE DATOS (e.g., 'proyecto_2025_2027')
        //    Consultamos la configuración para obtener el valor del campo 'database' de la conexión temporal.
        $dbName = config("database.connections.{$dynamicConnectionName}.database");

        // 3. Crear el nombre de tabla totalmente cualificado: DATABASE_NAME.nombre_de_tabla_pivote
        //    Esto es lo que MySQL necesita para el join entre bases de datos.
        $fullyQualifiedPivotTable = $dbName . '.profesor_modulo';
        
        return $this->belongsToMany(
            Profesor::class,                // Target: Profesor (usa la conexión 'mysql' por defecto)
            $fullyQualifiedPivotTable,      // Ahora usa el NOMBRE REAL DE LA BD para el join
            'modulo_id',                    // FK de este modelo en la pivote
            'profesor_id'                   // FK del modelo relacionado en la pivote
        )->using(ProfesorModulo::class); // Mantenemos el modelo pivote
    }
}