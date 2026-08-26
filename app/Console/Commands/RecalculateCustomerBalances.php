<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\Facture;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateCustomerBalances extends Command
{
    protected $signature = 'customers:recalculate-balances {--dry-run : Affiche les écarts sans rien modifier}';

    protected $description = 'Recalcule total_taken, total_repaid et balance de chaque client à partir de ses factures';

    /**
     * Les trois compteurs de `customers` sont tenus par incréments, à chaque vente
     * et à chaque paiement. Un incrément manqué — une facture supprimée sans reprise
     * des compteurs, un signe inversé — s'installe définitivement : rien ne le
     * rattrape ensuite.
     *
     * Les factures, elles, se sont révélées fiables : montant_total correspond aux
     * lignes de vente et reste au montant encore dû, sans un seul écart. C'est donc
     * d'elles que l'on repart.
     *
     * Convention : balance = total_taken - total_repaid, un solde POSITIF signifiant
     * que le client doit de l'argent.
     */
    public function handle(): int
    {
        $simulation = (bool) $this->option('dry-run');
        $lignes = [];
        $corriges = 0;

        foreach (Customer::orderBy('id')->get() as $client) {
            $factures = Facture::where('customer_id', $client->id)->get();

            // reste est le seul champ que tiennent à jour les deux chemins (création
            // de la vente et encaissement) ; la somme des versements, elle, ignore
            // les avances saisies sans ligne de paiement.
            $pris = round((float) $factures->sum('montant_total'), 2);
            $du = round((float) $factures->sum('reste'), 2);
            $rendu = round($pris - $du, 2);

            $ecartSolde = round($du - (float) $client->balance, 2);
            $ecartPris = round($pris - (float) $client->total_taken, 2);
            $ecartRendu = round($rendu - (float) $client->total_repaid, 2);

            if (abs($ecartSolde) < 0.01 && abs($ecartPris) < 0.01 && abs($ecartRendu) < 0.01) {
                continue;
            }

            $corriges++;
            $lignes[] = [
                mb_strimwidth($client->customerName ?? '—', 0, 22, '…'),
                $this->montant($client->balance).' → '.$this->montant($du),
                $this->montant($ecartPris),
                $this->montant($ecartRendu),
            ];

            if (! $simulation) {
                DB::table('customers')->where('id', $client->id)->update([
                    'total_taken' => $pris,
                    'total_repaid' => $rendu,
                    'balance' => $du,
                    'updated_at' => now(),
                ]);
            }
        }

        if ($corriges === 0) {
            $this->info('Tous les compteurs sont déjà cohérents avec les factures.');

            return self::SUCCESS;
        }

        $this->table(['Client', 'Solde (avant → après)', 'Écart pris', 'Écart rendu'], $lignes);

        $this->info(sprintf(
            '%d client(s) %s.',
            $corriges,
            $simulation ? 'seraient corrigés (--dry-run : rien n\'a été modifié)' : 'corrigés'
        ));

        return self::SUCCESS;
    }

    private function montant(float|string|null $v): string
    {
        return number_format((float) $v, 0, ',', ' ');
    }
}
