<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategoriesSeeder extends Seeder
{
    public function run()
    {
        $categories = [
            ['slug' => 'peinture', 'category_type' => 'Peinture & Droguerie', 'description' => 'Peintures, pinceaux et produits de finition'],
            ['slug' => 'outillage', 'category_type' => 'Outillage', 'description' => 'Outils à main pour le bâtiment et le jardin'],
            ['slug' => 'serrurerie', 'category_type' => 'Serrurerie & Sécurité', 'description' => 'Cadenas, serrures et verrous'],
            ['slug' => 'plomberie', 'category_type' => 'Plomberie', 'description' => 'Tuyaux, raccords et robinetterie'],
            ['slug' => 'electricite', 'category_type' => 'Électricité', 'description' => 'Câbles, ampoules et petit matériel électrique'],
            ['slug' => 'quincaillerie-generale', 'category_type' => 'Quincaillerie Générale', 'description' => 'Clous, vis et articles divers'],
        ];

        foreach ($categories as $category) {
            DB::table('categories')->updateOrInsert(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
