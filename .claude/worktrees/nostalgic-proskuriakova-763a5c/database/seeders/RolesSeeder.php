<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        // Keep IDs stable because the codebase uses numeric role_id checks (e.g. 1 admin, 3 shop user).
        $roles = [
            ['id' => 1, 'slug' => 'admin', 'nameRole' => 'Admin'],
            // Legacy/compat role often used as "manager" in existing data
            ['id' => 2, 'slug' => 'superuser', 'nameRole' => 'Superuser'],
            ['id' => 3, 'slug' => 'shopmanager', 'nameRole' => 'Manager'],
            ['id' => 4, 'slug' => 'vendeur', 'nameRole' => 'Vendeur'],
            ['id' => 5, 'slug' => 'comptable', 'nameRole' => 'Comptable'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(
                ['id' => $role['id']],
                [
                    'slug' => $role['slug'],
                    'nameRole' => $role['nameRole'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}

