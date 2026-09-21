<?php

namespace App\Imports;

use App\Models\BusOilChange;
use Maatwebsite\Excel\Concerns\ToModel;

class BusOilChangesImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {
        return new BusOilChange([
            //
        ]);
    }
}
