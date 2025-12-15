<?php

namespace App\Imports;

use App\Models\Modulo;
use Maatwebsite\Excel\Concerns\ToModel;

class ModulosImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Modulo([
            //
        ]);
    }
}
