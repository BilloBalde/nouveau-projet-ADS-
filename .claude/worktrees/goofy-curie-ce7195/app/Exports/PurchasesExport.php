<?php

namespace App\Exports;

use App\Models\Purchase;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithDrawings;

class PurchasesExport implements FromView
{
    public function view(): View
    {
        $dataTable = Purchase::all(); // Adjust the query as needed

        return view('purchases.export', compact('dataTable'));
    }
}
