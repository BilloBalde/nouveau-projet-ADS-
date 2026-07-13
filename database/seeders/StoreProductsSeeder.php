<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreProductsSeeder extends Seeder
{
    public function run()
    {
        $storeId = DB::table('stores')->where('store_name', 'Magasin Central')->value('id')
            ?? DB::table('stores')->value('id');

        if (!$storeId) {
            return;
        }

        $products = DB::table('products')->pluck('id', 'sku');

        $stocks = [
            ['sku' => 'SKU-CLAV-001', 'quantity' => 50],
            ['sku' => 'SKU-SOURIS-001', 'quantity' => 80],
            ['sku' => 'SKU-IMPR-001', 'quantity' => 10],
            ['sku' => 'SKU-CHAISE-001', 'quantity' => 20],
            ['sku' => 'SKU-LAMPE-001', 'quantity' => 30],
        ];

        foreach ($stocks as $stock) {
            $productId = $products[$stock['sku']] ?? null;
            if (!$productId) {
                continue;
            }

            DB::table('store_products')->updateOrInsert(
                ['store_id' => $storeId, 'product_id' => $productId],
                ['store_id' => $storeId, 'product_id' => $productId, 'quantity' => $stock['quantity']]
            );
        }
    }
}
