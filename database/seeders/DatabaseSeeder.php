<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\UsersSeeder;
use Database\Seeders\RolesSeeder;
use Database\Seeders\PlacesSeeder;
use Database\Seeders\StoresSeeder;
use Database\Seeders\ProductsSeeder;
use Database\Seeders\CustomersSeeder;
use Database\Seeders\CategoriesSeeder;
use Database\Seeders\StoreProductsSeeder;
use Database\Seeders\CategoryProductsSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            RolesSeeder::class,
            UsersSeeder::class,
            PlacesSeeder::class,
            StoresSeeder::class,
            CategoriesSeeder::class,
            ExpenseCategoriesSeeder::class,
            ProductsSeeder::class,
            CategoryProductsSeeder::class,
            StoreProductsSeeder::class,
            CustomersSeeder::class,
        ]);
    }
}
