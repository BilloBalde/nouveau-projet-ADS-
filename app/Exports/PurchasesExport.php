<?php

namespace App\Exports;

use App\Models\Purchase;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class PurchasesExport implements FromView
{
    public function view(): View
    {
        $dataTable = Purchase::all(); // Adjust the query as needed

        return view('purchases.export', compact('dataTable'));
    }
}
