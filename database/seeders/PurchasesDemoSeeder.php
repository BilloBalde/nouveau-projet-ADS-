<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PurchasesDemoSeeder extends Seeder
{
    public function run()
    {
        $storeId = DB::table('stores')->where('store_name', 'Quincaillerie Madina')->value('id');
        $products = DB::table('products')->select('id', 'sku', 'price_sale')->get()->keyBy('sku');

        if (! $storeId || $products->isEmpty()) {
            return;
        }

        // [sku, quantité, jours avant aujourd'hui]
        $purchases = [
            ['CBF-PNT-001', 20, 30],
            ['CBF-MRT-001', 15, 28],
            ['CBF-CAD-001', 30, 25],
            ['CBF-TUY-001', 15, 20],
            ['CBF-FIL-001', 8, 18],
            ['CBF-CLO-001', 40, 15],
            ['CBF-SER-001', 10, 14],
            ['CBF-AMP-001', 60, 10],
        ];

        foreach ($purchases as $i => [$sku, $quantity, $daysAgo]) {
            $product = $products[$sku] ?? null;
            if (! $product) {
                continue;
            }

            $date = Carbon::now()->subDays($daysAgo);

            DB::table('purchases')->updateOrInsert(
                ['numeroPurchase' => 'ACH-'.$date->format('Ymd').'-'.sprintf('%03d', $i + 1)],
                [
                    'product_id' => $product->id,
                    'store_id' => $storeId,
                    'price' => (float) $product->price_sale,
                    'quantity' => $quantity,
                    'description' => 'Réapprovisionnement '.$date->translatedFormat('d/m/Y'),
                    'created_at' => $date,
                    'updated_at' => $date,
                ]
            );
        }
    }
}
