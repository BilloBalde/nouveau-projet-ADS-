<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlacesSeeder extends Seeder
{
    public function run()
    {
        $places = [
            ['placeName' => 'Conakry', 'countryName' => 'Guinea', 'description' => 'Capitale de la Guinee'],
            ['placeName' => 'Labe', 'countryName' => 'Guinea', 'description' => 'Ville de la Guinee'],
            ['placeName' => 'Kankan', 'countryName' => 'Guinea', 'description' => 'Ville de la Guinee'],
        ];

        foreach ($places as $place) {
            DB::table('places')->updateOrInsert(
                ['placeName' => $place['placeName']],
                $place
            );
        }
    }
}
