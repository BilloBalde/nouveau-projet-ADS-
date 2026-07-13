<?php

namespace App\Exports;

use App\Models\Logistic;
use Maatwebsite\Excel\Concerns\FromCollection;

class LogisticsExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Logistic::all();
    }
}
