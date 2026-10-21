<?php

// app/Exports/ProductsExport.php

namespace App\Exports;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithTitle;

class ProductsExport implements FromView, WithChunkReading, WithTitle
{
    public function view(): View
    {
        // Utiliser chunk pour éviter de charger tout en mémoire
        $dataTable = Product::with('categories', 'stores')->get();

        $userStoreId = auth()->user()->isStoreScoped()
                ? auth()->user()->assignedStoreId()
                : null;

        return view('products.export', compact('dataTable', 'userStoreId'));
    }

    public function title(): string
    {
        return 'Produits';
    }

    public function chunkSize(): int
    {
        return 100; // Exporter par lots de 100
    }
}
