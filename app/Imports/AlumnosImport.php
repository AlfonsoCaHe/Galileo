<?php

namespace App\Imports;

use App\Models\Alumno;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow; // Importante para leer cabeceras

class AlumnosImport implements ToModel, WithHeadingRow
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        // $row contiene los datos de la fila del Excel.
        // Las claves son los nombres de las columnas en minúsculas y snake_case.
        // Ejemplo: Si en Excel la columna es "Nombre Completo", aquí se usa 'nombre_completo'.

        return new Alumno([
            'nombre'   => $row['nombre'],
            'email'    => $row['email'],
            'telefono' => $row['telefono'], 
            // Añade aquí el resto de campos de tu tabla que coincidan con el Excel
        ]);
    }
}