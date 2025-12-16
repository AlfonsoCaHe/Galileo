<?php

namespace App\Imports;

use App\Models\Proyecto;
use App\Models\Ras;
use App\Models\Criterio;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;

class RasCriteriosImport implements ToCollection
{
    protected $proyecto;
    protected $moduloId; // ID del módulo forzado

    public function __construct(Proyecto $proyecto, $moduloId)
    {
        $this->proyecto = $proyecto;
        $this->moduloId = $moduloId;

        // Configuramos la conexión dinámica inmediatamente
        $this->setDynamicConnection();
    }

    public function collection(Collection $rows)
    {
        $currentRa = null; // Memoria del último RA procesado

        foreach ($rows as $row) {
            $textoColumnaA = $row[0] ?? null; 
            $textoColumnaB = $row[1] ?? null;

            // Saltar filas vacías
            if (!$textoColumnaA && !$textoColumnaB) continue;

            // ---------------------------------------------------------
            // CASO 1: Es un RA (Columna A empieza por "RA")
            // ---------------------------------------------------------
            if ($textoColumnaA && Str::startsWith(trim($textoColumnaA), 'RA')) {
                
                // Parseamos "RA1. Descripción..." o "RA 1: Descripción"
                // Usamos una regex simple para separar "RA" + "Numero" del resto
                preg_match('/^(RA\s*\d+)[\.:\s]+(.*)$/i', trim($textoColumnaA), $matches);

                $codigoRa      = $matches[1] ?? substr(trim($textoColumnaA), 0, 4); // Fallback
                $descripcionRa = $matches[2] ?? substr(trim($textoColumnaA), 4);

                // Creamos el RA vinculado al MÓDULO PASADO EN EL CONSTRUCTOR
                $currentRa = Ras::create([
                    'codigo'      => trim($codigoRa),
                    'descripcion' => trim($descripcionRa),
                    'modulo_id'   => $this->moduloId // <--- AQUÍ USAMOS EL ID DIRECTO
                ]);
                
                continue; 
            }

            // ---------------------------------------------------------
            // CASO 2: Es un Criterio (Columna B empieza por letra + paréntesis)
            // ---------------------------------------------------------
            // A veces Séneca pone el criterio en la Columna A si no hay RA en esa fila, 
            // chequeamos ambas priorizando B.
            $textoCriterio = $textoColumnaB ? $textoColumnaB : ($textoColumnaA ? $textoColumnaA : null);

            // Buscamos patrón "a) ...", "b) ...", "a. ..."
            if ($currentRa && $textoCriterio && preg_match('/^([a-zñ]{1})\)/i', trim($textoCriterio), $matches)) {
                
                $letra = $matches[1]; // "a"
                $ce = $letra . ')';   // "a)"
                
                // Limpiamos la descripción quitando el "a) " del principio
                $descripcion = Str::after(trim($textoCriterio), ')');

                Criterio::create([
                    'ce'          => $ce,
                    'descripcion' => trim($descripcion),
                    'ras_id'      => $currentRa->id_ras
                ]);
            }
        }
    }

    /**
     * Configuración de la conexión dinámica
     */
    private function setDynamicConnection()
    {
        $connectionName = 'dynamic_import_ras_' . $this->proyecto->id_base_de_datos;
        
        $config = config('database.connections.mysql');
        $config['database'] = $this->proyecto->conexion;
        
        Config::set("database.connections.{$connectionName}", $config);
        DB::purge($connectionName);

        // Forzamos conexión en los modelos
        Ras::getConnectionResolver()->setDefaultConnection($connectionName);
        Criterio::getConnectionResolver()->setDefaultConnection($connectionName);
    }
}