<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersSeeder extends Seeder
{
    public function run()
    {
        $roles = DB::table('roles')->pluck('id', 'slug');

        $users = [
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'phone' => '620000000',
                'email' => 'admin@example.com',
                'role_id' => $roles['admin'] ?? 1,
                'status' => 'Active',
                'token' => hash('sha256', Str::random(16)),
                'password' => Hash::make('Admin@123'),
                'motdepasse' => 'Admin@123',
                'description' => 'Compte administrateur principal.',
                'profilePic' => 'default.png',
            ],
            [
                'name' => 'General Manager',
                'username' => 'manager',
                'phone' => '620000001',
                'email' => 'manager@example.com',
                'role_id' => $roles['superuser'] ?? 2,
                'status' => 'Active',
                'token' => hash('sha256', Str::random(16)),
                'password' => Hash::make('Manager@123'),
                'motdepasse' => 'Manager@123',
                'description' => 'Responsable général des boutiques.',
                'profilePic' => 'default.png',
            ],
            [
                'name' => 'Store User',
                'username' => 'storeuser',
                'phone' => '620000002',
                'email' => 'storeuser@example.com',
                'role_id' => $roles['shopmanager'] ?? 3,
                'status' => 'Active',
                'token' => hash('sha256', Str::random(16)),
                'password' => Hash::make('User@123'),
                'motdepasse' => 'User@123',
                'description' => 'Utilisateur boutique.',
                'profilePic' => 'default.png',
            ],
        ];

        foreach ($users as $user) {
            DB::table('users')->updateOrInsert(
                ['email' => $user['email']],
                $user
            );
        }
    }
}
