<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Le tableau de bord lançait soixante-huit requêtes pour s'afficher, dont une
 * par ligne du tableau des factures : la vue interrogeait les ventes à
 * l'intérieur de sa boucle. Invisible en démonstration — dix-neuf produits —
 * mais le compte monte avec l'historique, et c'est la première page que le
 * gérant ouvre chaque matin.
 *
 * Ce test ne mesure pas une vitesse, qui dépend de la machine : il compte les
 * requêtes, ce qui est stable et dit exactement la même chose.
 */
class TableauDeBordRequetesTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_tableau_de_bord_ne_lance_pas_de_requete_par_ligne(): void
    {
        Artisan::call('db:seed', ['--force' => true]);
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($admin)->get(route('home'))->assertOk();
        $requetes = collect(DB::getQueryLog());
        DB::disableQueryLog();

        // La requête qui était dans la boucle : groupée par facture, elle ne
        // doit plus être jouée qu'une fois pour tout le tableau.
        $parFacture = $requetes->filter(fn ($r) => str_contains($r['query'], 'numeroFacture')
            && str_contains($r['query'], 'SUM(quantity)'))->count();

        $this->assertLessThanOrEqual(1, $parFacture,
            "Les quantités sont encore demandées facture par facture ($parFacture requêtes).");

        // Compter les requêtes du tableau de bord n'aurait pas de sens : il
        // affiche une quarantaine d'indicateurs, et ce nombre bougera. Ce qui
        // doit rester vrai, c'est qu'aucune requête n'est rejouée : une
        // répétition signale une boucle, et elle croîtra avec les données.
        $repetitions = $requetes
            ->map(fn ($r) => preg_replace('/\s+/', ' ', $r['query']))
            ->countBy()->sortDesc();

        $this->assertLessThanOrEqual(3, $repetitions->first(),
            "Une requête est rejouée {$repetitions->first()} fois — il reste une boucle :\n  "
            .substr((string) $repetitions->keys()->first(), 0, 150));
    }

    /**
     * Le regroupement des quatre périodes en une requête change la façon de
     * calculer, pas le résultat. Ce test recalcule chaque période à part —
     * l'ancienne méthode — et exige l'égalité, chiffre par chiffre.
     */
    public function test_les_chiffres_par_periode_sont_inchanges(): void
    {
        Artisan::call('db:seed', ['--force' => true]);
        $admin = User::where('email', 'admin@example.com')->firstOrFail();

        $vue = $this->actingAs($admin)->get(route('home'))->assertOk();
        $overview = $vue->viewData('overview');

        $periodes = [
            'jour' => [now()->startOfDay(), now()->endOfDay()],
            'semaine' => [now()->startOfWeek(), now()->endOfWeek()],
            'mois' => [now()->startOfMonth(), now()->endOfMonth()],
            'annee' => [now()->startOfYear(), now()->endOfYear()],
        ];

        $entrepots = DB::table('stores')->where('type', 'entrepot')->pluck('id');

        foreach ($periodes as $cle => $bornes) {
            $attenduCa = (float) DB::table('sales')->whereBetween('created_at', $bornes)->sum('prixTotal');
            $attenduMarge = (float) DB::table('sales')->whereBetween('created_at', $bornes)->sum('interet');
            $attenduAssoc = (float) DB::table('sales')->whereBetween('created_at', $bornes)
                ->whereNotNull('fund_id')->sum('prixTotal');
            $attenduDirect = $entrepots->isEmpty() ? 0.0 : (float) DB::table('sales')
                ->whereBetween('created_at', $bornes)->whereIn('store_id', $entrepots)->sum('prixTotal');

            $this->assertEqualsWithDelta($attenduCa, $overview['sales'][$cle]['ca'], 0.01, "CA $cle");
            $this->assertEqualsWithDelta($attenduMarge, $overview['sales'][$cle]['marge'], 0.01, "Marge $cle");
            $this->assertEqualsWithDelta($attenduAssoc, $overview['sales'][$cle]['assoc_ca'], 0.01, "CA associés $cle");
            $this->assertEqualsWithDelta($attenduDirect, $overview['sales'][$cle]['direct'], 0.01, "Ventes directes $cle");
        }
    }
}
