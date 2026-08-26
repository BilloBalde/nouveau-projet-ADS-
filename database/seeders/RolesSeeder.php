<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        // Les identifiants ET les slugs sont figés : le code s'appuie sur les deux.
        // Côté numérique via les constantes de App\Models\User (ROLE_ADMIN = 1…),
        // côté slug dans Authentification et RoleController — par exemple
        // `Role::whereNotIn('slug', ['admin', 'customer'])` pour la liste des rôles
        // assignables, ou `$role->slug === 'user'` pour rattacher un vendeur à une
        // boutique. Modifier une valeur ici casse silencieusement ces contrôles.
        $roles = [
            ['id' => 1, 'slug' => 'admin',     'nameRole' => 'Administrator'],
            ['id' => 2, 'slug' => 'superuser', 'nameRole' => 'Manager'],
            ['id' => 3, 'slug' => 'user',      'nameRole' => 'Vendeur'],
            ['id' => 4, 'slug' => 'customer',  'nameRole' => 'Client'],
            ['id' => 5, 'slug' => 'comptable', 'nameRole' => 'Comptable'],
            ['id' => 6, 'slug' => 'achat',     'nameRole' => 'Responsable Achats'],
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

