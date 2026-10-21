<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

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
