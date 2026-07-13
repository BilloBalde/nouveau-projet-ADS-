<?php
// app/Jobs/ExportProductsJob.php

namespace App\Jobs;

use App\Models\Product;
use App\Exports\ProductsExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ExportProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;
    protected $userRole;

    public function __construct($userId, $userRole)
    {
        $this->userId = $userId;
        $this->userRole = $userRole;
    }

    public function handle()
    {
        auth()->loginUsingId($this->userId);
        
        $filename = 'exports/products_' . date('Y-m-d_His') . '.xlsx';
        
        Excel::store(new ProductsExport, $filename, 'public');
        
        // Notifier l'utilisateur que l'export est prêt
        // Vous pouvez stocker le lien en session ou en base de données
        session(['export_file' => Storage::url($filename)]);
    }
}