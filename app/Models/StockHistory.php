<?php
// app/Models/StockHistory.php

namespace App\Models;

use App\Models\Product;
use Illuminate\Database\Eloquent\Model;

class StockHistory extends Model
{
    protected $fillable = [
        'store_id',
        'type',
        'amount',
        'dispo_before',
        'dispo_after',
        'reference',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }
     public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Récupère la dernière disponibilité connue pour un magasin.
     */
    public static function getLastDispo($storeId)
    {
        return self::where('store_id', $storeId)
            ->latest('id')
            ->value('dispo_after') ?? 0;
    }
}