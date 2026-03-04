<?php

namespace App\Imports;

use App\Models\Proyecto;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Carbon\Carbon;

class RasCriteriosImport implements ToCollection
{
    protected $proyecto;
    protected $moduloId;
    protected $connectionName;

    public function __construct(Proyecto $proyecto, $moduloId)
    {
        $this->proyecto = $proyecto;
        $this->moduloId = $moduloId;
        
        // Configuramos la conexión dinámica al instanciar la clase
        $this->setDynamicConnection();
    }

    /**
     * Este método recibe todas las filas del Excel y las procesa
     */
    public function collection(Collection $rows)
    {
        $currentRaId = null; // Aquí recordaremos el ID del último RA encontrado
        $now = Carbon::now();

        foreach ($rows as $row) {
            // 1. Limpieza de datos (Quitar espacios, comillas, caracteres invisibles)
            $colA = isset($row[0]) ? trim((string)$row[0], " \t\n\r\0\x0B\"") : '';
            $colB = isset($row[1]) ? trim((string)$row[1], " \t\n\r\0\x0B\"") : '';

            // Si la fila está vacía, la saltamos
            if ($colA === '' && $colB === '') continue;

            /**
             *  Para detectar un RA, el texto debe ser "RA" seguido de números
             *
             *  Al encontrar un RA, ignoramos las cabeceras del Excel (filas 1-10) porque no cumplen este patrón.
             */
            if (preg_match('/^RA\s*(\d+)[\.:\s]+(.*)$/i', $colA, $matches)) {
                
                $codigo = 'RA' . $matches[1]; // Ej: RA1
                $descripcion = trim($matches[2], " \t\n\r\0\x0B\".");
                
                // Generamos UUID manual
                $currentRaId = Str::uuid()->toString();

                // Insertamos directamente en la tabla 'ras' usando la conexión dinámica
                DB::connection($this->connectionName)->table('ras')->insert([
                    'id_ras'      => $currentRaId,
                    'codigo'      => $codigo,
                    'descripcion' => $descripcion,
                    'modulo_id'   => $this->moduloId, // Vinculamos al módulo de la URL
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
                
                continue; // Pasamos a la siguiente fila
            }

            /**
             * Para detectar un Criterio, el patrón es letra + paréntesis "a)"
             * Lo buscamos en la columna B o A si no está en B
             */
            $textoCriterio = $colB ?: $colA; 

            // Solo insertamos si ya hemos encontrado un RA ($currentRaId no es null)
            if ($currentRaId && preg_match('/^([a-zñ]{1})\)(.*)$/i', $textoCriterio, $matches)) {
                
                $ce = $matches[1] . ')'; // Ej: a)
                $descCriterio = trim($matches[2], " \t\n\r\0\x0B\".");

                DB::connection($this->connectionName)->table('criterios')->insert([
                    'id_criterio' => Str::uuid()->toString(),
                    'ce'          => $ce,
                    'descripcion' => $descCriterio,
                    'ras_id'      => $currentRaId, // Vinculamos al RA anterior
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            }
        }
    }

    /**
     * Configura la conexión a la base de datos del proyecto
     */
    private function setDynamicConnection()
    {
        $this->connectionName = 'dynamic_import_' . $this->proyecto->id_base_de_datos;
        
        // Clonamos la config de mysql y cambiamos la BD target
        $config = config('database.connections.mysql');
        $config['database'] = $this->proyecto->conexion;
        
        Config::set("database.connections.{$this->connectionName}", $config);
        DB::purge($this->connectionName);
    }
}