<?php

namespace App\Services;

use App\Models\Associate;
use App\Models\AssociateProfitShare;
use App\Models\AssociateYearClosing;
use App\Models\Expense;
use App\Models\Sale;
use App\Support\ModeDeVente;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Compte des associés, année par année.
 *
 *   report de l'année précédente
 * + part du bénéfice, saisie à la main
 * − sorties données à l'associé (les dépenses)
 * = solde
 *
 * À la clôture, un solde positif est versé à l'associé ; un solde négatif —
 * il a pris plus que sa part — est reporté sur l'année suivante. La part du
 * bénéfice que personne ne reçoit reste à la boutique.
 */
class AssociesService
{
    /**
     * Compte d'un associé pour une année : figé s'il est clôturé, calculé
     * sinon.
     *
     * @return array{annee:int, report:float, part:float, sorties:float, solde:float, a_verser:float, a_reporter:float, cloture:?AssociateYearClosing}
     */
    public function compte(Associate $associe, int $annee): array
    {
        $cloture = AssociateYearClosing::where('associate_id', $associe->id)->where('year', $annee)->first();

        if ($cloture) {
            return [
                'annee' => $annee,
                'report' => (float) $cloture->carried_in,
                'part' => (float) $cloture->profit_share,
                'sorties' => (float) $cloture->withdrawals,
                'solde' => (float) $cloture->balance,
                'a_verser' => (float) $cloture->paid,
                'a_reporter' => (float) $cloture->carried_out,
                'cloture' => $cloture,
            ];
        }

        $report = $this->reportEntrant($associe, $annee);
        $part = (float) AssociateProfitShare::where('associate_id', $associe->id)->where('year', $annee)->value('amount');
        $sorties = (float) $this->sortiesDe($associe, $annee)->sum('amount');
        $solde = round($report + $part - $sorties, 2);

        return [
            'annee' => $annee,
            'report' => $report,
            'part' => $part,
            'sorties' => $sorties,
            'solde' => $solde,
            'a_verser' => max(0.0, $solde),
            'a_reporter' => min(0.0, $solde),
            'cloture' => null,
        ];
    }

    /** Sorties d'un associé sur une année, de la plus récente à la plus ancienne. */
    public function sortiesDe(Associate $associe, int $annee)
    {
        [$debut, $fin] = $this->bornes($annee);

        return Expense::with('store:id,store_name', 'user:id,name')
            ->where('associate_id', $associe->id)
            ->whereBetween('created_at', [$debut, $fin])
            ->orderByDesc('created_at')
            ->orderByDesc('id');
    }

    /**
     * Ce que l'associé apporte de l'année précédente : le report de sa
     * dernière clôture, négatif s'il avait pris plus que sa part.
     */
    public function reportEntrant(Associate $associe, int $annee): float
    {
        return (float) AssociateYearClosing::where('associate_id', $associe->id)
            ->where('year', '<', $annee)
            ->orderByDesc('year')
            ->value('carried_out');
    }

    /**
     * Bénéfice de l'année, en repère pour fixer les parts : la marge des
     * comptages validés en mode inventaire, celle des ventes en mode caisse.
     * Les sorties d'associés n'en sont pas retirées : elles ne sont pas une
     * charge de la boutique, elles s'imputent sur la part de chacun.
     */
    public function beneficeDeLAnnee(int $annee): float
    {
        if (ModeDeVente::parInventaire()) {
            return (float) app(InventaireRapportService::class)->tableauDeBord(null, $annee)['marge_annee'];
        }

        return (float) Sale::whereBetween('created_at', $this->bornes($annee))->sum('interet');
    }

    public function estCloturee(int $annee): bool
    {
        return AssociateYearClosing::where('year', $annee)->exists();
    }

    /**
     * Associés à faire figurer pour une année : les actifs, et ceux qui y ont
     * un mouvement ou un report — un associé désactivé en cours d'année doit
     * quand même être soldé.
     */
    public function associesDeLAnnee(int $annee): Collection
    {
        if ($this->estCloturee($annee)) {
            return Associate::whereIn('id', AssociateYearClosing::where('year', $annee)->pluck('associate_id'))
                ->orderBy('name')->get();
        }

        [$debut, $fin] = $this->bornes($annee);

        return Associate::query()
            ->where('active', true)
            ->orWhereHas('profitShares', fn ($q) => $q->where('year', $annee))
            ->orWhereHas('expenses', fn ($q) => $q->whereBetween('created_at', [$debut, $fin]))
            ->orWhereHas('closings', fn ($q) => $q->where('year', '<', $annee)->where('carried_out', '!=', 0))
            ->orderBy('name')
            ->get();
    }

    /**
     * Enregistre les parts du bénéfice d'une année. Une part vide ou nulle
     * est retirée : l'associé ne reçoit rien cette année-là.
     *
     * @param  array<int, float|int|string|null>  $parts  [associate_id => montant]
     */
    public function enregistrerParts(int $annee, array $parts, ?int $userId = null): void
    {
        if ($this->estCloturee($annee)) {
            throw new RuntimeException("L'année $annee est clôturée : les parts ne peuvent plus changer.");
        }

        DB::transaction(function () use ($annee, $parts, $userId) {
            foreach ($parts as $associeId => $montant) {
                $montant = round((float) $montant, 2);

                if ($montant < 0) {
                    throw new RuntimeException('Une part du bénéfice ne peut pas être négative.');
                }

                if ($montant == 0) {
                    AssociateProfitShare::where('associate_id', $associeId)->where('year', $annee)->delete();

                    continue;
                }

                AssociateProfitShare::updateOrCreate(
                    ['associate_id' => $associeId, 'year' => $annee],
                    ['amount' => $montant, 'user_id' => $userId ?? auth()->id()]
                );
            }
        });
    }

    /**
     * Clôture l'année pour tous ses associés : chaque compte est figé, le
     * solde positif est versé, le solde négatif reporté.
     *
     * Les années se ferment dans l'ordre : le report d'une année est le
     * point de départ de la suivante, il doit être connu avant.
     *
     * @return Collection<int, AssociateYearClosing>
     */
    public function cloturer(int $annee, ?int $userId = null): Collection
    {
        if ($this->estCloturee($annee)) {
            throw new RuntimeException("L'année $annee est déjà clôturée.");
        }

        if ($annee > (int) now()->year) {
            throw new RuntimeException("L'année $annee n'est pas encore commencée.");
        }

        $enAttente = $this->anneeAnterieureOuverte($annee);
        if ($enAttente !== null) {
            throw new RuntimeException("Clôturez d'abord l'année $enAttente : son report est le point de départ de $annee.");
        }

        $associes = $this->associesDeLAnnee($annee);
        if ($associes->isEmpty()) {
            throw new RuntimeException("Aucun associé à solder pour $annee.");
        }

        return DB::transaction(function () use ($annee, $associes, $userId) {
            return $associes->map(function (Associate $associe) use ($annee, $userId) {
                $compte = $this->compte($associe, $annee);

                return AssociateYearClosing::create([
                    'associate_id' => $associe->id,
                    'year' => $annee,
                    'carried_in' => $compte['report'],
                    'profit_share' => $compte['part'],
                    'withdrawals' => $compte['sorties'],
                    'balance' => $compte['solde'],
                    'paid' => $compte['a_verser'],
                    'carried_out' => $compte['a_reporter'],
                    'user_id' => $userId ?? auth()->id(),
                    'closed_at' => now(),
                ]);
            });
        });
    }

    /**
     * Première année antérieure, avec une part ou une sortie, qui n'a pas été
     * clôturée ; null s'il n'y en a pas.
     */
    public function anneeAnterieureOuverte(int $annee): ?int
    {
        $cloturees = AssociateYearClosing::distinct()->pluck('year')->map(fn ($a) => (int) $a)->all();

        $avecParts = AssociateProfitShare::where('year', '<', $annee)->distinct()->pluck('year')->map(fn ($a) => (int) $a);
        $avecSorties = Expense::whereNotNull('associate_id')
            ->where('created_at', '<', Carbon::create($annee, 1, 1)->startOfDay())
            ->get(['created_at'])
            ->map(fn (Expense $sortie) => (int) $sortie->created_at->year);

        $ouvertes = $avecParts->merge($avecSorties)->unique()->reject(fn ($a) => in_array($a, $cloturees, true))->sort()->values();

        return $ouvertes->first();
    }

    /** @return array{0:Carbon, 1:Carbon} */
    private function bornes(int $annee): array
    {
        return [Carbon::create($annee, 1, 1)->startOfDay(), Carbon::create($annee, 12, 31)->endOfDay()];
    }
}
