<?php

namespace App\Imports;

use App\Models\Criterio;
use Maatwebsite\Excel\Concerns\ToModel;

class CriteriosImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Criterio([
            //
        ]);
    }
}
