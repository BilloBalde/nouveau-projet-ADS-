<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;

/**
 * Applique l'identité TELICO à la fiche entreprise : nom, logo et e-mail de
 * contact, repris par l'en-tête du logiciel, le site, les factures, devis,
 * reçus et exports. Les autres champs (téléphone, adresse…) ne changent pas.
 */
class ApplyTelicoIdentity extends Command
{
    protected $signature = 'telico:identite
        {--nom=TELICO : Nom affiché sur les documents}
        {--email=Mamadoulawydiallo@gmail.com : E-mail de contact}';

    protected $description = 'Met la fiche entreprise aux couleurs de TELICO (nom, logo, e-mail)';

    public function handle(): int
    {
        $company = Company::current();

        $valeurs = [
            'name' => $this->option('nom'),
            'logo' => 'telico.png',
            'email' => $this->option('email'),
        ];

        if (! $company) {
            $company = Company::create($valeurs + ['address' => 'Conakry, Guinée', 'about' => 'Quincaillerie générale']);
            $this->info('Fiche entreprise créée.');
        } else {
            $avant = $company->only(array_keys($valeurs));
            $company->update($valeurs);
            $this->table(['Champ', 'Avant', 'Après'], collect($valeurs)->map(fn ($v, $k) => [$k, $avant[$k] ?? '—', $v])->values()->all());
        }

        $this->info('Identité TELICO appliquée.');

        return self::SUCCESS;
    }
}
