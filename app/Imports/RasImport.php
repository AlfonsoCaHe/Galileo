<?php

namespace App\Imports;

use App\Models\Ras;
use Maatwebsite\Excel\Concerns\ToModel;

class RasImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new Ras([
            //
        ]);
    }
}
