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
            ['sku' => 'SKU-CLAV-001', 'slug' => 'bureau'],
            ['sku' => 'SKU-SOURIS-001', 'slug' => 'bureau'],
            ['sku' => 'SKU-IMPR-001', 'slug' => 'bureau'],
            ['sku' => 'SKU-CHAISE-001', 'slug' => 'maison'],
            ['sku' => 'SKU-LAMPE-001', 'slug' => 'maison'],
        ];

        foreach ($links as $link) {
            $productId = $products[$link['sku']] ?? null;
            $categoryId = $categories[$link['slug']] ?? null;
            if (!$productId || !$categoryId) {
                continue;
            }

            DB::table('category_products')->updateOrInsert(
                ['product_id' => $productId, 'category_id' => $categoryId],
                ['product_id' => $productId, 'category_id' => $categoryId]
            );
        }
    }
}
