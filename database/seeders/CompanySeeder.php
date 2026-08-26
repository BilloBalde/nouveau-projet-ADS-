<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Sans cette ligne, une installation neuve n'a aucune société : le titre des
     * pages est vide et le logo pointe vers « /companies/ », introuvable. Ces
     * valeurs sont modifiables ensuite depuis Paramètres > Profil de l'entreprise.
     *
     * Idempotent : repérée par son nom, elle n'est pas dupliquée à chaque passage.
     */
    public function run(): void
    {
        Company::updateOrCreate(
            ['name' => 'COLEAH BANDEYA & FILS'],
            [
                'address' => 'Madina, Ecole Gare Routière Dabola Camion, Conakry',
                'email' => 'abdulghadirybalde@gmail.com',
                'phone' => '625 45 35 45',
                'phone_secondary' => '628 59 31 10',
                'logo' => 'ADK.png',
                'about' => 'Quincaillerie générale : peinture, baguette, pelle, pioche, serrure et divers.',
            ]
        );
    }
}
