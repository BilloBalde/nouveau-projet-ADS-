<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ExpenseCategoriesSeeder extends Seeder
{
    public function run()
    {
        $categories = [
            ['slug' => 'transport', 'categoryName' => 'Transport'],
            ['slug' => 'fournitures', 'categoryName' => 'Fournitures'],
            ['slug' => 'maintenance', 'categoryName' => 'Maintenance'],
            ['slug' => 'salaires', 'categoryName' => 'Salaires'],
            ['slug' => 'divers', 'categoryName' => 'Divers'],
        ];

        foreach ($categories as $category) {
            DB::table('expense_categories')->updateOrInsert(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
