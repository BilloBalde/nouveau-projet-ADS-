<?php

namespace App\Exports;

use App\Models\Logistic;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

class LogisticsExport implements FromCollection
{
    /**
     * @return Collection
     */
    public function collection()
    {
        return Logistic::all();
    }
}
