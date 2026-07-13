<?php
// app/Exports/ProductsExport.php

namespace App\Exports;

use App\Models\Product;
use App\Models\Store;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class ProductsExport implements FromView, WithTitle, WithChunkReading
{
    public function view(): View
    {
        // Utiliser chunk pour éviter de charger tout en mémoire
        $dataTable = Product::with('categories', 'stores')->get();
        
        $userStoreId = auth()->user()->role_id == 3
                ? Store::where('user_id', auth()->user()->id)->value('id')
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