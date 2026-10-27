<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

/**
 * Fiche entreprise d'une base neuve.
 *
 * Sans elle, une installation neuve n'a aucune société : le titre des pages est
 * vide et l'en-tête n'a rien à afficher. Les valeurs sont ensuite modifiables
 * depuis Paramètres > Profil de l'entreprise, qui reste la source de vérité :
 * .env ne fait que poser le point de départ.
 *
 * Quatre situations, et trois d'entre elles ont déjà mal tourné :
 *
 *  - Fiche absente, COMPANY_* renseigné → elle est créée. C'est la livraison
 *    normale : le .env du client, puis « php artisan db:seed ».
 *
 *  - Fiche absente, COMPANY_* absent → une fiche neutre, au nom de APP_NAME.
 *    Les valeurs de repli de config/brand.php sont celles de la première
 *    installation ; les écrire dans la base d'un autre commerçant lui donnait
 *    le nom, l'adresse et l'e-mail de quelqu'un d'autre, à imprimer sur ses
 *    factures.
 *
 *  - Fiche présente, COMPANY_* renseigné → elle est mise à jour. Repérée par
 *    son existence et non par son nom : avec updateOrCreate sur le nom,
 *    changer COMPANY_NAME pour un nouveau client ajoutait une seconde fiche
 *    au lieu de remplacer la première — et Company::current() prend la plus
 *    récente, ce qui rendait l'affichage dépendant de l'ordre d'insertion.
 *
 *  - Fiche présente, COMPANY_* absent → on n'y touche pas. Les seeders sont
 *    annoncés rejouables pour reprendre les permissions d'une mise à jour ;
 *    les relancer ne doit pas effacer l'identité saisie dans l'interface.
 */
class CompanySeeder extends Seeder
{
    public function run(): void
    {
        $existante = Company::query()->orderBy('id')->first();
        $renseignee = (bool) config('brand.entreprise_renseignee');

        if ($existante && ! $renseignee) {
            return;
        }

        if ($renseignee) {
            $fiche = config('brand.entreprise');
        } else {
            $fiche = [
                'name' => config('app.name'),
                'address' => '',
                'about' => '',
                'email' => null,
                'phone' => null,
                'phone_secondary' => null,
                'logo' => null,
            ];
        }

        $existante ? $existante->update($fiche) : Company::create($fiche);
    }
}
