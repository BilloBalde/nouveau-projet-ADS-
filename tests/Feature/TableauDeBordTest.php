<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Facture;
use App\Models\Partner;
use App\Models\Place;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Role;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\User;
use App\Services\PurchaseOrderCostingService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Tableau de bord (/home).
 *
 * L'écran le plus consulté de l'application n'avait aucun test : ses requêtes
 * mensuelles utilisaient MONTH(), propre à MySQL, donc la page renvoyait une
 * erreur 500 sous SQLite — le moteur de cette suite. Aucun filet de sécurité
 * n'était donc possible, ni sur le rendu, ni sur les chiffres.
 */
class TableauDeBordTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Store $magasin;

    private Store $kaloum;

    private Customer $client;

    private Product $ciment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->admin = $this->user('Patron', User::ROLE_ADMIN);
        Place::create(['placeName' => 'Conakry', 'countryName' => 'Guinée', 'description' => 'test']);
        $this->magasin = $this->magasin('Magasin principal', true);
        $this->kaloum = $this->magasin('Kaloum');

        $this->client = Customer::create(['customerName' => 'Client X', 'mark' => 'CLX', 'tel' => '620000000', 'address' => 'Madina']);
        $this->ciment = Product::create(['libelle' => 'Ciment', 'sku' => 'CIM', 'description' => 'x',
            'unite' => 'pcs', 'price' => 60000, 'price_sale' => 45000]);
        StoreProduct::create(['store_id' => $this->magasin->id, 'product_id' => $this->ciment->id, 'quantity' => 80]);
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

    private function facture(int $montant, Carbon $date, ?Store $magasin = null): Facture
    {
        $facture = Facture::create(['numero_facture' => 'F-'.uniqid(), 'store_id' => ($magasin ?? $this->magasin)->id,
            'customer_id' => $this->client->id, 'montant_total' => $montant, 'avance' => 0, 'reste' => $montant]);

        // created_at est renseigné par le modèle : on le repositionne pour
        // placer la facture dans le mois voulu.
        $facture->forceFill(['created_at' => $date, 'updated_at' => $date])->save();

        return $facture;
    }

    private function achat(int $montantUnitaire, int $quantite, Carbon $date): PurchaseOrder
    {
        $achat = PurchaseOrder::create([
            'reference' => 'ACH-'.uniqid(), 'store_id' => $this->magasin->id,
            'partner_id' => Partner::firstOrCreate(['name' => 'Fournisseur'])->id,
            'created_by' => $this->admin->id, 'origin' => 'guinee', 'exchange_rate_used' => 1,
            'transport_cost_gnf' => 0, 'customs_cost_gnf' => 0, 'other_fees_gnf' => 0,
            'status' => 'received', 'date_emis' => $date->toDateString(),
        ]);

        app(PurchaseOrderCostingService::class)->costOrder($achat, [
            ['product_id' => $this->ciment->id, 'quantity' => $quantite, 'unit_price_foreign' => $montantUnitaire],
        ]);

        $achat->forceFill(['created_at' => $date, 'updated_at' => $date])->save();

        return $achat;
    }

    // -----------------------------------------------------------------

    public function test_le_tableau_de_bord_s_affiche(): void
    {
        $this->facture(500_000, Carbon::now());
        $this->achat(1_000, 10, Carbon::now());

        $this->actingAs($this->admin)->get(route('home'))->assertOk();
    }

    public function test_le_graphique_mensuel_place_chaque_facture_dans_son_mois(): void
    {
        $janvier = Carbon::create(Carbon::now()->year, 1, 15);
        $mars = Carbon::create(Carbon::now()->year, 3, 10);

        $this->facture(1_000_000, $janvier);
        $this->facture(250_000, $mars);
        $this->facture(750_000, $mars);

        $sales = $this->actingAs($this->admin)->get(route('home'))->assertOk()->viewData('sales');

        $this->assertEquals(1_000_000, $sales[0], 'Janvier');
        $this->assertEquals(0, $sales[1], 'Février');
        $this->assertEquals(1_000_000, $sales[2], 'Mars : les deux factures du mois cumulées');
        $this->assertCount(12, $sales);
    }

    /**
     * Le graphique est étiqueté Jan…Déc : il porte sur l'année en cours.
     * Le groupement par mois ne filtrait pas l'année, donc janvier de l'année
     * passée s'ajoutait à janvier de celle-ci — invisible la première année
     * d'exploitation, faux dès la seconde.
     */
    public function test_le_graphique_mensuel_ignore_les_annees_precedentes(): void
    {
        $this->facture(1_000_000, Carbon::create(Carbon::now()->year, 1, 15));
        $this->facture(9_000_000, Carbon::create(Carbon::now()->year - 1, 1, 15));

        $sales = $this->actingAs($this->admin)->get(route('home'))->assertOk()->viewData('sales');

        $this->assertEquals(1_000_000, $sales[0], 'Janvier de l\'année passée a été additionné à celui de l\'année en cours.');
    }

    public function test_les_achats_du_graphique_sont_portes_en_negatif_et_par_mois(): void
    {
        $this->achat(1_000, 10, Carbon::create(Carbon::now()->year, 2, 5));   // 10 000
        $this->achat(2_000, 10, Carbon::create(Carbon::now()->year - 1, 2, 5)); // ignoré

        $purchases = $this->actingAs($this->admin)->get(route('home'))->assertOk()->viewData('purchases');

        $this->assertEquals(-10_000, $purchases[1], 'Février, en négatif, sans l\'année passée.');
        $this->assertEquals(0, $purchases[0]);
    }

    /** Une facture mise à la corbeille ne compte plus dans le chiffre d'affaires. */
    public function test_une_facture_supprimee_sort_du_graphique(): void
    {
        $mois = Carbon::now()->startOfMonth()->addDays(2);
        $gardee = $this->facture(400_000, $mois);
        $supprimee = $this->facture(600_000, $mois);
        $supprimee->delete();

        $sales = $this->actingAs($this->admin)->get(route('home'))->assertOk()->viewData('sales');

        $this->assertEquals(400_000, $sales[$mois->month - 1]);
        $this->assertNotNull($gardee->fresh());
    }

    /**
     * Un employé rattaché à un magasin ne voit que les chiffres de son
     * périmètre — le tableau de bord est le premier écran après la connexion.
     */
    public function test_un_vendeur_ne_voit_que_son_magasin(): void
    {
        $vendeur = $this->user('Awa', User::ROLE_STORE_MANAGER, $this->kaloum->id);
        $mois = Carbon::now()->startOfMonth()->addDays(2);

        $this->facture(5_000_000, $mois, $this->magasin);
        $this->facture(300_000, $mois, $this->kaloum);

        $sales = $this->actingAs($vendeur)->get(route('home'))->assertOk()->viewData('sales');

        $this->assertEquals(300_000, $sales[$mois->month - 1], 'Le vendeur voit le chiffre du magasin principal.');
    }

    /**
     * Les alertes de stock se calculent depuis store_products, pas depuis la
     * colonne products.pcs, qui a été retirée pour cette raison.
     */
    public function test_les_alertes_de_stock_viennent_du_stock_par_emplacement(): void
    {
        $alertes = $this->actingAs($this->admin)->get(route('home'))->assertOk()->viewData('stockAlerts');

        $this->assertSame(80, (int) $alertes->firstWhere('id', $this->ciment->id)->stock_total);
    }
}
