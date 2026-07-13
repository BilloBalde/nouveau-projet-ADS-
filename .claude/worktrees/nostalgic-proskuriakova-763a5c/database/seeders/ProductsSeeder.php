<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductsSeeder extends Seeder
{
    public function run()
    {
        $products = [
            [
                'libelle' => 'Clavier USB',
                'sku' => 'SKU-CLAV-001',

                'image' => 'default.png',
                'description' => 'Clavier filaire USB.',
            ],
            [
                'libelle' => 'Souris Optique',
                'sku' => 'SKU-SOURIS-001',

                'image' => 'default.png',
                'description' => 'Souris optique USB.',
            ],
            [
                'libelle' => 'Imprimante',
                'sku' => 'SKU-IMPR-001',

                'image' => 'default.png',
                'description' => 'Imprimante multifonction.',
            ],
            [
                'libelle' => 'Chaise Bureau',
                'sku' => 'SKU-CHAISE-001',

                'image' => 'default.png',
                'description' => 'Chaise de bureau ergonomique.',
            ],
            [
                'libelle' => 'Lampe LED',
                'sku' => 'SKU-LAMPE-001',

                'image' => 'default.png',
                'description' => 'Lampe LED pour bureau.',
            ],
        ];

        foreach ($products as $product) {
            DB::table('products')->updateOrInsert(
                ['sku' => $product['sku']],
                $product
            );
        }
    }
}
