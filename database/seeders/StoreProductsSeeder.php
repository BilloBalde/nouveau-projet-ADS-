<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreProductsSeeder extends Seeder
{
    public function run()
    {
        $mainStoreId = DB::table('stores')->where('store_name', 'Quincaillerie Madina')->value('id');
        $depotStoreId = DB::table('stores')->where('store_name', 'Dépôt Madina')->value('id');

        if (! $mainStoreId) {
            return;
        }

        $products = DB::table('products')->pluck('id', 'sku');

        // Stock de la boutique principale (vente au comptoir)
        $mainStock = [
            'CBF-PNT-001' => 25, 'CBF-PNT-002' => 30, 'CBF-PNC-001' => 60,
            'CBF-MRT-001' => 20, 'CBF-PEL-001' => 15, 'CBF-PIO-001' => 15, 'CBF-SCI-001' => 18,
            'CBF-CAD-001' => 40, 'CBF-SER-001' => 12, 'CBF-VER-001' => 20,
            'CBF-TUY-001' => 22, 'CBF-ROB-001' => 18, 'CBF-RAC-001' => 50,
            'CBF-FIL-001' => 10, 'CBF-AMP-001' => 80, 'CBF-DIS-001' => 25,
            'CBF-CLO-001' => 45, 'CBF-VIS-001' => 45, 'CBF-BRW-001' => 6,
        ];

        // Stock du dépôt (réserve, plus limité)
        $depotStock = [
            'CBF-PNT-001' => 15, 'CBF-MRT-001' => 10, 'CBF-CAD-001' => 20,
            'CBF-TUY-001' => 12, 'CBF-FIL-001' => 8, 'CBF-CLO-001' => 25, 'CBF-BRW-001' => 4,
        ];

        foreach ($mainStock as $sku => $quantity) {
            $productId = $products[$sku] ?? null;
            if (! $productId) {
                continue;
            }

            DB::table('store_products')->updateOrInsert(
                ['store_id' => $mainStoreId, 'product_id' => $productId],
                ['store_id' => $mainStoreId, 'product_id' => $productId, 'quantity' => $quantity]
            );
        }

        if ($depotStoreId) {
            foreach ($depotStock as $sku => $quantity) {
                $productId = $products[$sku] ?? null;
                if (! $productId) {
                    continue;
                }

                DB::table('store_products')->updateOrInsert(
                    ['store_id' => $depotStoreId, 'product_id' => $productId],
                    ['store_id' => $depotStoreId, 'product_id' => $productId, 'quantity' => $quantity]
                );
            }
        }
    }
}
