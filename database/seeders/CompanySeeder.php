<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Sans cette ligne, une installation neuve n'a aucune société : le titre des
     * pages est vide et l'en-tête n'a rien à afficher. Ces valeurs sont
     * modifiables ensuite depuis Paramètres > Profil de l'entreprise.
     *
     * La fiche vient de config/brand.php, donc de .env : le nom d'un client
     * n'est plus écrit dans le code, et livrer le suivant ne demande pas d'y
     * revenir.
     *
     * Idempotent : repérée par son nom, elle n'est pas dupliquée à chaque passage.
     */
    public function run(): void
    {
        $fiche = config('brand.entreprise');

        // Repérée par son existence, pas par son nom : avec updateOrCreate sur
        // le nom, changer COMPANY_NAME pour un nouveau client ajoutait une
        // seconde fiche au lieu de remplacer la première — et Company::current()
        // prend la plus récente, ce qui rendait l'affichage dépendant de l'ordre
        // d'insertion.
        $existante = Company::query()->orderBy('id')->first();

        $existante ? $existante->update($fiche) : Company::create($fiche);
    }
}
