<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\Product;
use App\Models\Role;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\User;
use App\Services\InventaireService;
use App\Support\ModeDeVente;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tableau de bord et rapport des ventes quand le chiffre d'affaires se déduit
 * des comptages de stock.
 *
 * Ces deux écrans lisaient la table des ventes, vide par construction dans ce
 * mode : la boutique vendait pour des millions et son écran d'accueil — le
 * premier qu'elle ouvre chaque matin — affichait zéro partout, pendant que le
 * rapport des ventes restait vide.
 */
class InventaireTableauDeBordTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Store $madina;

    private Store $kaloum;

    private Product $ciment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->seed(PermissionSeeder::class);
        config(['sales.mode' => ModeDeVente::INVENTAIRE]);

        $this->admin = $this->user('Patron', User::ROLE_ADMIN);
        Place::create(['placeName' => 'Conakry', 'countryName' => 'Guinée', 'description' => 'test']);
        $this->madina = $this->magasin('Madina', true);
        $this->kaloum = $this->magasin('Kaloum');

        // Revient 38 000, vente 50 000 → 12 000 de marge par sac.
        $this->ciment = Product::create(['libelle' => 'Ciment', 'sku' => 'CIM', 'description' => 'x',
            'unite' => 'pcs', 'price' => 50_000, 'price_sale' => 38_000, 'price_manuel' => true]);

        foreach ([$this->madina, $this->kaloum] as $m) {
            StoreProduct::create(['store_id' => $m->id, 'product_id' => $this->ciment->id, 'quantity' => 100]);
        }

        $this->actingAs($this->admin);
    }

    private function magasin(string $nom, bool $principal = false): Store
    {
        return Store::create(['store_name' => $nom, 'place_id' => Place::firstOrFail()->id, 'user_id' => $this->admin->id,
            'store_picture' => '', 'description' => 'x', 'status' => 1, 'type' => 'magasin', 'is_main' => $principal]);
    }

    private function user(string $name, int|string $role, ?int $storeId = null): User
    {
        $roleId = is_int($role) ? $role : (int) Role::where('slug', $role)->value('id');

        $user = User::create([
            'name' => $name, 'email' => str()->slug($name).uniqid().'@example.com', 'username' => str()->slug($name).uniqid(),
            'password' => Hash::make('password'), 'role_id' => $roleId, 'phone' => '620', 'status' => 'Active', 'token' => '',
        ]);

        if ($storeId) {
            $user->assignToStore($storeId);
        }

        return $user->fresh();
    }

    /** Pose le départ puis clôt une période : retourne le CA constaté. */
    private function periode(Store $magasin, int $debut, int $fin, string $dateDebut, string $dateFin): float
    {
        $svc = app(InventaireService::class);
        $svc->valider($svc->preparer($magasin->id, $dateDebut), [
            ['product_id' => $this->ciment->id, 'counted' => $debut],
        ]);
        $seance = $svc->valider($svc->preparer($magasin->id, $dateFin), [
            ['product_id' => $this->ciment->id, 'counted' => $fin],
        ]);

        return (float) $seance->total_revenue_gnf;
    }

    // -----------------------------------------------------------------

    public function test_le_tableau_de_bord_affiche_le_chiffre_des_comptages(): void
    {
        $annee = Carbon::now()->year;
        $ca = $this->periode($this->madina, 100, 30, "$annee-01-01", "$annee-01-31");
        $this->assertEquals(3_500_000, $ca, '70 vendus × 50 000');

        $reponse = $this->get(route('home'))->assertOk();

        $this->assertEquals(3_500_000, $reponse->viewData('ca_annee'));
        $this->assertEquals(840_000, $reponse->viewData('marge_annee'), '70 × (50 000 − 38 000)');
        $this->assertSame(70, $reponse->viewData('quantite_annee'));

        // Le chiffre est rattaché au mois où le comptage a clos la période.
        $parMois = $reponse->viewData('ca_par_mois');
        $this->assertEquals(3_500_000, $parMois[1], 'Janvier');
        $this->assertEquals(0, $parMois[2], 'Février');

        $reponse->assertSee('3,500,000.00 FG', false);
    }

    public function test_le_tableau_de_bord_cumule_les_boutiques(): void
    {
        $annee = Carbon::now()->year;
        $this->periode($this->madina, 100, 30, "$annee-02-01", "$annee-02-28");  // 3 500 000
        $this->periode($this->kaloum, 100, 60, "$annee-02-01", "$annee-02-28");  // 2 000 000

        $this->assertEquals(5_500_000, $this->get(route('home'))->assertOk()->viewData('ca_annee'));
    }

    /** Un employé rattaché ne voit que le chiffre de son périmètre. */
    public function test_un_vendeur_ne_voit_que_sa_boutique(): void
    {
        $annee = Carbon::now()->year;
        $this->periode($this->madina, 100, 30, "$annee-03-01", "$annee-03-31");  // 3 500 000
        $this->periode($this->kaloum, 100, 60, "$annee-03-01", "$annee-03-31");  // 2 000 000

        $vendeur = $this->user('Awa', 'responsable', $this->kaloum->id);

        $this->assertEquals(2_000_000, $this->actingAs($vendeur)->get(route('home'))->assertOk()->viewData('ca_annee'));
    }

    /** Un comptage non validé ne dit rien de ce qui a été vendu. */
    public function test_un_comptage_en_cours_n_entre_dans_aucun_total(): void
    {
        $annee = Carbon::now()->year;
        $this->periode($this->madina, 100, 30, "$annee-04-01", "$annee-04-30");
        app(InventaireService::class)->preparer($this->madina->id, "$annee-05-31");

        $reponse = $this->get(route('home'))->assertOk();

        $this->assertEquals(3_500_000, $reponse->viewData('ca_annee'), 'Le brouillon ne doit rien ajouter.');
        $this->assertSame(1, $reponse->viewData('comptages_en_cours'));
        $reponse->assertSee('comptage(s) en cours de saisie', false);
    }

    /** Sans comptage, l'écran explique pourquoi plutôt que d'afficher 0 sans mot dire. */
    public function test_sans_comptage_l_ecran_explique_ce_qui_manque(): void
    {
        $this->get(route('home'))->assertOk()
            ->assertSee('Aucun comptage validé', false)
            ->assertSee('le premier pose le stock de départ', false);
    }

    // -----------------------------------------------------------------
    // Rapport des ventes
    // -----------------------------------------------------------------

    public function test_le_rapport_des_ventes_totalise_les_periodes(): void
    {
        $annee = Carbon::now()->year;
        $this->periode($this->madina, 100, 30, "$annee-06-01", "$annee-06-30");
        $this->periode($this->kaloum, 100, 60, "$annee-06-01", "$annee-06-30");

        $reponse = $this->get(route('reports.sales', ['period' => 'monthly', 'month' => "$annee-06"]))->assertOk();

        $this->assertEquals(5_500_000, $reponse->viewData('totalAmount'), 'Les deux boutiques cumulées.');
        $this->assertSame(110, $reponse->viewData('totalQuantity'));
        $this->assertCount(1, $reponse->viewData('produits'), 'Un seul produit vendu, cumulé sur les deux boutiques.');
        $reponse->assertSee('Ciment');
    }

    public function test_le_rapport_se_filtre_par_boutique(): void
    {
        $annee = Carbon::now()->year;
        $this->periode($this->madina, 100, 30, "$annee-07-01", "$annee-07-31");
        $this->periode($this->kaloum, 100, 60, "$annee-07-01", "$annee-07-31");

        $reponse = $this->get(route('reports.sales', [
            'period' => 'monthly', 'month' => "$annee-07", 'store_id' => $this->kaloum->id,
        ]))->assertOk();

        $this->assertEquals(2_000_000, $reponse->viewData('totalAmount'));
    }

    public function test_le_rapport_ignore_les_periodes_hors_plage(): void
    {
        $annee = Carbon::now()->year;
        $this->periode($this->madina, 100, 30, "$annee-08-01", "$annee-08-31");

        $reponse = $this->get(route('reports.sales', ['period' => 'monthly', 'month' => "$annee-09"]))->assertOk();

        $this->assertEquals(0, $reponse->viewData('totalAmount'));
        $reponse->assertSee('Aucun comptage clos sur cette période', false);
    }

    // -----------------------------------------------------------------
    // Le mode caisse n'est pas touché
    // -----------------------------------------------------------------

    public function test_en_mode_caisse_les_deux_ecrans_restent_ceux_d_avant(): void
    {
        config(['sales.mode' => ModeDeVente::CAISSE]);

        $home = $this->get(route('home'))->assertOk();
        $this->assertNotNull($home->viewData('total_sales'), 'Le tableau de bord de la caisse doit rester en place.');

        $rapport = $this->get(route('reports.sales'))->assertOk();
        $this->assertNotNull($rapport->viewData('sales'));
    }
}
