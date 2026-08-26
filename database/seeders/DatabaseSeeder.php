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
use Database\Seeders\SalesDemoSeeder;
use Database\Seeders\PurchasesDemoSeeder;

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
            // Sans lui, une installation neuve n'a aucune permission : les menus et
            // les écrans protégés par @permission restent invisibles, y compris pour
            // l'administrateur. Doit passer après les rôles, qu'il référence.
            PermissionSeeder::class,
            CompanySeeder::class,
            UsersSeeder::class,
            PlacesSeeder::class,
            StoresSeeder::class,
            CategoriesSeeder::class,
            ExpenseCategoriesSeeder::class,
            ProductsSeeder::class,
            CategoryProductsSeeder::class,
            StoreProductsSeeder::class,
            CustomersSeeder::class,
            SalesDemoSeeder::class,
            PurchasesDemoSeeder::class,
        ]);
    }
}
