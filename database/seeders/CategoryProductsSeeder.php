<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoryProductsSeeder extends Seeder
{
    public function run()
    {
        $categories = DB::table('categories')->pluck('id', 'slug');
        $products = DB::table('products')->pluck('id', 'sku');

        $links = [
            'peinture' => ['CBF-PNT-001', 'CBF-PNT-002', 'CBF-PNC-001'],
            'outillage' => ['CBF-MRT-001', 'CBF-PEL-001', 'CBF-PIO-001', 'CBF-SCI-001'],
            'serrurerie' => ['CBF-CAD-001', 'CBF-SER-001', 'CBF-VER-001'],
            'plomberie' => ['CBF-TUY-001', 'CBF-ROB-001', 'CBF-RAC-001'],
            'electricite' => ['CBF-FIL-001', 'CBF-AMP-001', 'CBF-DIS-001'],
            'quincaillerie-generale' => ['CBF-CLO-001', 'CBF-VIS-001', 'CBF-BRW-001'],
        ];

        foreach ($links as $slug => $skus) {
            $categoryId = $categories[$slug] ?? null;
            if (! $categoryId) {
                continue;
            }

            foreach ($skus as $sku) {
                $productId = $products[$sku] ?? null;
                if (! $productId) {
                    continue;
                }

                DB::table('category_products')->updateOrInsert(
                    ['product_id' => $productId, 'category_id' => $categoryId],
                    ['product_id' => $productId, 'category_id' => $categoryId]
                );
            }
        }
    }
}
