<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;

/**
 * Écrit la fiche entreprise du .env dans la base.
 *
 * CompanySeeder ne sert que les bases neuves. Sur une installation déjà en
 * service — une reprise, une base copiée d'un autre client — la fiche
 * enregistrée reste celle d'avant, et c'est elle que portent l'en-tête, les
 * factures, les PDF et les e-mails : renseigner .env n'y change rien.
 *
 * Cette commande fait le pont. Elle modifie la fiche existante au lieu d'en
 * créer une seconde : Company::current() prend la plus récente, et deux fiches
 * en base donnent un résultat qui dépend de l'ordre d'insertion.
 */
class AppliquerIdentite extends Command
{
    protected $signature = 'identite:appliquer {--force : Ne pas demander confirmation}';

    protected $description = 'Applique au logiciel la fiche entreprise définie dans .env (COMPANY_*)';

    public function handle(): int
    {
        $voulu = collect(config('brand.entreprise'))
            ->map(fn ($valeur) => $valeur === '' ? null : $valeur)
            ->all();

        if (blank($voulu['name'] ?? null)) {
            $this->error('COMPANY_NAME n\'est pas renseigné dans .env — rien à appliquer.');

            return self::FAILURE;
        }

        $fiche = Company::current();

        if (! $fiche) {
            Company::create($voulu);
            $this->info("Fiche entreprise créée : {$voulu['name']}.");

            return self::SUCCESS;
        }

        $lignes = collect($voulu)
            ->map(fn ($apres, $champ) => [$champ, $fiche->{$champ} ?: '—', $apres ?: '—'])
            ->values()->all();

        $this->table(['Champ', 'Avant', 'Après'], $lignes);

        if (! $this->option('force') && ! $this->confirm('Appliquer ces valeurs à la fiche enregistrée ?', true)) {
            $this->line('Abandonné, rien n\'a été modifié.');

            return self::SUCCESS;
        }

        $fiche->update($voulu);

        // Une seconde fiche rendrait l'affichage dépendant de l'ordre
        // d'insertion : on le signale plutôt que de supprimer en silence.
        if (($autres = Company::where('id', '!=', $fiche->id)->count()) > 0) {
            $this->warn("Attention : $autres autre(s) fiche(s) en base. Company::current() prend la plus récente.");
        }

        $this->info("Identité appliquée : {$fiche->name}.");
        $this->line('Le logo se dépose depuis Paramètres > Profil de l\'entreprise.');

        return self::SUCCESS;
    }
}
