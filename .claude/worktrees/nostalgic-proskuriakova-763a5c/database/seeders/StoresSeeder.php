<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoresSeeder extends Seeder
{
    public function run()
    {
        $placeId = DB::table('places')->where('placeName', 'Conakry')->value('id')
            ?? DB::table('places')->value('id');
        $userId = DB::table('users')->where('email', 'manager@example.com')->value('id')
            ?? DB::table('users')->value('id');

        if (!$placeId || !$userId) {
            return;
        }

        $stores = [
            [
                'store_name' => 'Magasin Central',
                'place_id' => $placeId,
                'user_id' => $userId,
                'store_picture' => 'store.png',
                'status' => 1,
                'description' => 'Boutique principale.',
            ],
            [
                'store_name' => 'Magasin Secondaire',
                'place_id' => $placeId,
                'user_id' => $userId,
                'store_picture' => 'store.png',
                'status' => 1,
                'description' => 'Boutique secondaire.',
            ],
        ];

        foreach ($stores as $store) {
            DB::table('stores')->updateOrInsert(
                ['store_name' => $store['store_name']],
                $store
            );
        }
    }
}
