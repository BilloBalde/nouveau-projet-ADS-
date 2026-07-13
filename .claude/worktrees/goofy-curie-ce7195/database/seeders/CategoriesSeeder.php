<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesSeeder extends Seeder
{
    public function run()
    {
        $categories = [
            ['slug' => 'electronique', 'category_type' => 'Electronique', 'description' => 'Articles électroniques'],
            ['slug' => 'maison', 'category_type' => 'Maison', 'description' => 'Articles pour la maison'],
            ['slug' => 'bureau', 'category_type' => 'Bureau', 'description' => 'Fournitures de bureau'],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->updateOrInsert(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
