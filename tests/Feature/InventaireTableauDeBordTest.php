<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Partner;
use App\Models\Place;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\User;
use App\Services\InventaireService;
use App\Services\PurchaseOrderCostingService;
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

        // Les scénarios de comptage portent des dates fixes. Sans horloge
        // figée, elles basculent dans le futur avec le temps qui passe — et un
        // comptage daté du futur est désormais refusé, à juste titre.
        $this->travelTo('2027-12-31 12:00:00');

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

    // -----------------------------------------------------------------
    // Le prix de vente, seule source du chiffre d'affaires
    // -----------------------------------------------------------------

    /**
     * Un achat ne doit JAMAIS recalculer le prix de vente dans ce mode : il
     * est la seule chose qui transforme « 120 sacs manquants » en francs. Le
     * laisser suivre le dernier prix de revient ferait varier le chiffre
     * d'affaires d'une période au gré des achats, sans décision de personne.
     */
    public function test_un_achat_ne_touche_jamais_au_prix_de_vente(): void
    {
        // Produit qui n'a PAS le drapeau « prix fixé à la main » : en mode
        // caisse, un achat le recalculerait.
        $riz = Product::create(['libelle' => 'Riz', 'sku' => 'RIZ', 'description' => 'x',
            'unite' => 'carton', 'price' => 80_000, 'price_sale' => 60_000, 'price_manuel' => false]);

        $achat = PurchaseOrder::create([
            'reference' => 'ACH-'.uniqid(), 'store_id' => $this->madina->id,
            'partner_id' => Partner::firstOrCreate(['name' => 'F'])->id,
            'created_by' => $this->admin->id, 'origin' => 'guinee', 'exchange_rate_used' => 1,
            'transport_cost_gnf' => 0, 'customs_cost_gnf' => 0, 'other_fees_gnf' => 0,
            'status' => 'received', 'date_emis' => now()->toDateString(),
        ]);
        app(PurchaseOrderCostingService::class)->costOrder($achat, [
            ['product_id' => $riz->id, 'quantity' => 10, 'unit_price_foreign' => 1_000],
        ]);

        $riz->refresh();
        $this->assertEquals(1_000, $riz->price_sale, 'Le prix de revient doit suivre le dernier achat.');
        $this->assertEquals(80_000, $riz->price, 'Le prix de vente a été recalculé alors qu\'il valorise les inventaires.');
    }

    /** Le même achat, en mode caisse, recalcule bien le prix : rien n'a changé là-bas. */
    public function test_en_mode_caisse_un_achat_recalcule_toujours_le_prix(): void
    {
        config(['sales.mode' => ModeDeVente::CAISSE, 'pricing.sale_margin_percent' => 30]);

        $riz = Product::create(['libelle' => 'Riz', 'sku' => 'RIZ', 'description' => 'x',
            'unite' => 'carton', 'price' => 80_000, 'price_sale' => 60_000, 'price_manuel' => false]);

        $achat = PurchaseOrder::create([
            'reference' => 'ACH-'.uniqid(), 'store_id' => $this->madina->id,
            'partner_id' => Partner::firstOrCreate(['name' => 'F'])->id,
            'created_by' => $this->admin->id, 'origin' => 'guinee', 'exchange_rate_used' => 1,
            'transport_cost_gnf' => 0, 'customs_cost_gnf' => 0, 'other_fees_gnf' => 0,
            'status' => 'received', 'date_emis' => now()->toDateString(),
        ]);
        app(PurchaseOrderCostingService::class)->costOrder($achat, [
            ['product_id' => $riz->id, 'quantity' => 10, 'unit_price_foreign' => 1_000],
        ]);

        $this->assertEquals(1_300, $riz->fresh()->price, '1 000 + 30 % : le comportement historique.');
    }

    /** À la création d'un produit, le gérant saisit son prix et il est conservé. */
    public function test_le_prix_saisi_a_la_creation_est_conserve(): void
    {
        $categorie = Category::forceCreate(['slug' => 'materiaux', 'category_type' => 'produit', 'description' => 'x']);

        $this->post(route('produits.store'), [
            'libelle' => 'Tôle', 'sku' => 'TOL', 'description' => 'x', 'unite' => 'pcs',
            'categories' => [$categorie->id],
            'price_sale' => 60_000,
            'price' => 95_000,          // décidé par le gérant, pas 60 000 + 30 %
            'stock_initial' => 10,
            'store_id' => $this->madina->id,
        ])->assertSessionHasNoErrors()->assertRedirect();

        $produit = Product::where('sku', 'TOL')->sole();
        $this->assertEquals(95_000, $produit->price, 'Le prix saisi doit être conservé tel quel.');
        $this->assertEquals(60_000, $produit->price_sale);
        $this->assertTrue($produit->prixFixeALaMain());
    }

    /** Et le formulaire le laisse saisir, au lieu de l'afficher en lecture seule. */
    public function test_le_formulaire_laisse_saisir_le_prix_de_vente(): void
    {
        $html = $this->get(route('produits.create'))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<input[^>]+id="price"[^>]+required/', $html, 'Le prix doit être saisissable.');
        $this->assertDoesNotMatchRegularExpression('/<input[^>]+id="price"[^>]+readonly/', $html);
        $this->assertStringContainsString('c\'est lui qui valorise vos inventaires', $html);
    }

    // -----------------------------------------------------------------
    // Les écrans qui ne peuvent rien contenir dans ce mode
    // -----------------------------------------------------------------

    /**
     * Ventes flash et ventes tierces se saisissent au point de vente, fermé en
     * mode inventaire : ces écrans ne peuvent rien contenir, par construction.
     * Les laisser au menu enverrait le gérant sur des pages vides sans lui
     * dire pourquoi.
     */
    public function test_les_rapports_de_caisse_sont_fermes(): void
    {
        foreach (['reports.flash', 'reports.tiers', 'reports.tiers.pdf', 'reports.tiers.excel'] as $route) {
            $this->get(route($route))->assertForbidden();
        }

        $this->post(route('reports.tiers.settle'), [])->assertForbidden();
    }

    public function test_le_menu_ne_propose_plus_les_rapports_de_caisse(): void
    {
        $reponse = $this->get(route('inventaires.index'))->assertOk();

        $reponse->assertDontSee('Ventes flash');
        $reponse->assertDontSee('Ventes tierces');
        // Les deux rapports qui gardent un sens restent proposés.
        $reponse->assertSee('Rapport des ventes');
        $reponse->assertSee('Rapport journalier');
    }

    /** En mode caisse, ces écrans restent accessibles : rien n'a changé là-bas. */
    public function test_en_mode_caisse_les_rapports_de_caisse_restent_ouverts(): void
    {
        config(['sales.mode' => ModeDeVente::CAISSE]);

        $this->get(route('reports.flash'))->assertOk();
        $this->get(route('reports.tiers'))->assertOk();
        $this->get(route('home'))->assertOk()->assertSee('Ventes flash');
    }

    // -----------------------------------------------------------------
    // Sans boutique en ligne : la chaîne facture → paiement → dette
    // -----------------------------------------------------------------

    /**
     * payments.facture_id est une clé étrangère obligatoire : pas de facture,
     * pas de paiement. Sans ventes saisies ni boutique en ligne, aucune
     * facture ne peut naître — donc ni paiement, ni dette, ni commande.
     */
    public function test_les_ecrans_sans_objet_sont_fermes(): void
    {
        foreach (['payments.index', 'admin.debt-reminders.index', 'admin.orders.index',
            'admin.orders.confirmed', 'admin.orders.factures'] as $route) {
            $this->get(route($route))->assertForbidden();
        }
    }

    public function test_le_menu_ne_propose_plus_ces_ecrans(): void
    {
        $reponse = $this->get(route('inventaires.index'))->assertOk();

        foreach (['Liste des paiements', 'Rappels de dettes', 'Commandes à valider',
            'Factures e-commerce', 'Liste des ventes'] as $entree) {
            $reponse->assertDontSee($entree);
        }
    }

    /**
     * Le parcours d'achat du site public est fermé lui aussi : une commande
     * passée que personne ne verrait dans l'administration serait pire qu'une
     * boutique fermée.
     */
    public function test_le_parcours_d_achat_du_site_public_est_ferme(): void
    {
        $this->app['auth']->forgetGuards();

        $this->get(route('panier'))->assertForbidden();
        $this->post(route('cart.add', $this->ciment->id))->assertForbidden();
    }

    /**
     * Les fiches produits publiques ne se contentent plus de masquer leurs
     * boutons d'achat : la vitrine entière est fermée. Ce commerçant n'a pas de
     * boutique en ligne, et une page de catalogue ouverte exposait son stock et
     * ses prix sans que rien ne les relève.
     *
     * La règle complète est tenue par VitrinePubliqueFermeeTest.
     */
    public function test_les_fiches_produits_publiques_sont_fermees(): void
    {
        $this->get(route('productDetail', $this->ciment->id))->assertForbidden();
        $this->get(route('products.index'))->assertForbidden();
    }

    /** En mode caisse, tout cela reste ouvert : rien n'a changé là-bas. */
    public function test_en_mode_caisse_la_boutique_en_ligne_reste_ouverte(): void
    {
        config(['sales.mode' => ModeDeVente::CAISSE]);

        $this->get(route('payments.index'))->assertOk();
        $this->get(route('admin.orders.index'))->assertOk();
        $this->get(route('home'))->assertOk()->assertSee('Liste des paiements');

        $this->app['auth']->forgetGuards();
        $this->get(route('panier'))->assertOk();
        $this->get(route('productDetail', $this->ciment->id))->assertOk()->assertSee('onclick="buyNow(', false);
    }
}
