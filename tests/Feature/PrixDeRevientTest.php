<?php

namespace Tests\Feature;

use App\Models\Fund;
use App\Models\FundTransaction;
use App\Models\Partner;
use App\Models\Place;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\User;
use App\Services\PurchaseOrderCostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Prix de revient d'un achat (PurchaseOrderCostingService).
 *
 * C'est le calcul dont tout le reste dépend : transport, douane et frais
 * divers sont répartis entre les lignes au prorata du volume (CBM), ce qui
 * donne le coût réel par article. Ce coût fixe ensuite le prix plancher de
 * chaque vente (Product::plancherDeVente), les marges, les intérêts et les
 * relevés d'associés.
 *
 * Les montants attendus sont écrits en clair, calculés à la main, pour qu'une
 * dérive du calcul se voie sur le chiffre et pas seulement sur un total.
 */
class PrixDeRevientTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Store $entrepot;
    private Store $magasin;
    private Product $ciment;
    private Product $carton;
    private Partner $fournisseur;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $this->admin = User::create([
            'name' => 'Patron', 'email' => 'patron'.uniqid().'@example.com', 'username' => 'patron'.uniqid(),
            'password' => Hash::make('password'), 'role_id' => User::ROLE_ADMIN, 'phone' => '620', 'status' => 'Active', 'token' => '',
        ]);

        Place::create(['placeName' => 'Conakry', 'countryName' => 'Guinée', 'description' => 'test']);
        $this->magasin = $this->emplacement('Magasin principal', Store::TYPE_MAGASIN, true);
        $this->entrepot = $this->emplacement('Entrepôt 5', Store::TYPE_ENTREPOT);

        $this->ciment = Product::create(['libelle' => 'Ciment', 'sku' => 'CIM', 'description' => 'x', 'unite' => 'pcs']);
        $this->carton = Product::create(['libelle' => 'Carton', 'sku' => 'CTN', 'description' => 'x', 'unite' => 'carton']);
        $this->fournisseur = Partner::create(['name' => 'Fournisseur Chine']);

        $this->actingAs($this->admin);
    }

    private function emplacement(string $nom, string $type, bool $principal = false): Store
    {
        return Store::create(['store_name' => $nom, 'place_id' => Place::firstOrFail()->id, 'user_id' => $this->admin->id,
            'store_picture' => '', 'description' => 'x', 'status' => 1, 'type' => $type, 'is_main' => $principal]);
    }

    private function achat(array $attributs = []): PurchaseOrder
    {
        return PurchaseOrder::create(array_merge([
            'reference' => 'ACH-'.uniqid(),
            'store_id' => $this->entrepot->id,
            'partner_id' => $this->fournisseur->id,
            'created_by' => $this->admin->id,
            'origin' => 'chine',
            'currency_code' => 'CNY',
            'exchange_rate_used' => 1300,
            'transport_cost_gnf' => 0,
            'customs_cost_gnf' => 0,
            'other_fees_gnf' => 0,
            'status' => 'received',
            'date_emis' => now()->toDateString(),
        ], $attributs));
    }

    private function service(): PurchaseOrderCostingService
    {
        return app(PurchaseOrderCostingService::class);
    }

    private function stock(Store $emplacement, Product $produit, ?int $fundId = null): int
    {
        return (int) DB::table('store_products')
            ->where('store_id', $emplacement->id)
            ->where('product_id', $produit->id)
            ->when($fundId, fn ($q) => $q->where('fund_id', $fundId), fn ($q) => $q->whereNull('fund_id'))
            ->sum('quantity');
    }

    // -----------------------------------------------------------------
    // Répartition des frais
    // -----------------------------------------------------------------

    /**
     * Deux lignes, des volumes différents : les frais suivent le CBM.
     *
     * Taux 1 300 GNF/CNY. Transport 2 000 000, douane 1 000 000.
     *
     *   Ciment : 100 pcs à 2 CNY  = 2 600 GNF/u, 10 cartons de 10 à 0,5 CBM
     *            → 0,05 CBM/u × 100 = 5,000 CBM  → 5/6 des frais
     *   Carton :  50 u  à 10 CNY  = 13 000 GNF/u, 5 cartons de 10 à 0,2 CBM
     *            → 0,02 CBM/u × 50  = 1,000 CBM  → 1/6 des frais
     *
     *   Ciment : (260 000 + 1 666 666,67 + 833 333,33) / 100 = 27 600
     *   Carton : (650 000 +   333 333,33 + 166 666,67) /  50 = 23 000
     */
    public function test_les_frais_sont_repartis_au_prorata_du_cbm(): void
    {
        $achat = $this->achat(['transport_cost_gnf' => 2_000_000, 'customs_cost_gnf' => 1_000_000]);

        $this->service()->costOrder($achat, [
            ['product_id' => $this->ciment->id, 'quantity' => 100, 'cartons' => 10, 'pieces_per_carton' => 10,
                'unit_price_foreign' => 2, 'cbm_per_carton' => 0.5],
            ['product_id' => $this->carton->id, 'quantity' => 50, 'cartons' => 5, 'pieces_per_carton' => 10,
                'unit_price_foreign' => 10, 'cbm_per_carton' => 0.2],
        ]);

        $achat->load('items');
        $lignes = $achat->items->keyBy('product_id');
        $ciment = $lignes[$this->ciment->id];
        $carton = $lignes[$this->carton->id];

        // Conversion de la devise
        $this->assertEquals(2_600, $ciment->unit_price_gnf);
        $this->assertEquals(13_000, $carton->unit_price_gnf);

        // Le CBM se saisit par carton ; le CBM par unité s'en déduit.
        $this->assertEquals(0.05, $ciment->cbm_per_unit);
        $this->assertEquals(5.0, $ciment->line_total_cbm);
        $this->assertEquals(1.0, $carton->line_total_cbm);

        // Prix de revient atterri
        $this->assertEquals(27_600, $ciment->landed_unit_cost_gnf);
        $this->assertEquals(23_000, $carton->landed_unit_cost_gnf);

        // Aucun franc perdu en route : les parts reconstituent les frais saisis.
        $this->assertEquals(
            3_000_000,
            round($achat->items->sum('allocated_freight_gnf') + $achat->items->sum('allocated_customs_gnf'), 2)
        );

        $achat->refresh();
        $this->assertEquals(6.0, $achat->total_cbm);
        $this->assertEquals(3_910_000, $achat->total_landed_cost_gnf, 'Marchandise (910 000) + transport + douane.');
    }

    /**
     * Sans CBM saisi (oubli, ou achat local sans volume suivi), les frais se
     * répartissent à parts égales : l'argent dépensé doit toujours se
     * retrouver dans le prix de revient de quelque chose.
     */
    public function test_sans_cbm_les_frais_sont_partages_a_parts_egales(): void
    {
        $achat = $this->achat(['origin' => 'guinee', 'currency_code' => null, 'exchange_rate_used' => 1,
            'transport_cost_gnf' => 1_000, 'customs_cost_gnf' => 0]);

        $this->service()->costOrder($achat, [
            ['product_id' => $this->ciment->id, 'quantity' => 10, 'unit_price_foreign' => 1_000],
            ['product_id' => $this->carton->id, 'quantity' => 5, 'unit_price_foreign' => 2_000],
        ]);

        $achat->load('items');
        $lignes = $achat->items->keyBy('product_id');

        // 500 de transport chacun : (10 000 + 500)/10 et (10 000 + 500)/5
        $this->assertEquals(1_050, $lignes[$this->ciment->id]->landed_unit_cost_gnf);
        $this->assertEquals(2_100, $lignes[$this->carton->id]->landed_unit_cost_gnf);
        $this->assertEquals(1_000, $achat->items->sum('allocated_freight_gnf'));
        $this->assertNull($achat->fresh()->total_cbm, 'Aucun CBM saisi : le total doit rester vide, pas 0.');
    }

    /** Un achat local est saisi directement en GNF : pas de conversion. */
    public function test_un_achat_local_ne_convertit_rien(): void
    {
        $achat = $this->achat(['origin' => 'guinee', 'currency_code' => null, 'exchange_rate_used' => 1300]);

        $this->service()->costOrder($achat, [
            ['product_id' => $this->ciment->id, 'quantity' => 4, 'unit_price_foreign' => 5_000, 'cbm_per_carton' => 2],
        ]);

        $ligne = $achat->load('items')->items->first();
        $this->assertEquals(5_000, $ligne->unit_price_gnf, 'Le taux ne doit pas s\'appliquer à un achat en GNF.');
        $this->assertNull($ligne->unit_price_foreign);
        $this->assertNull($ligne->cbm_per_carton, 'Le CBM ne concerne que les achats import.');
        $this->assertEquals(5_000, $ligne->landed_unit_cost_gnf);
    }

    // -----------------------------------------------------------------
    // Effets de l'achat : stock, journal, prix
    // -----------------------------------------------------------------

    public function test_l_achat_entre_en_stock_a_la_destination_et_laisse_une_trace(): void
    {
        $achat = $this->achat(['transport_cost_gnf' => 0]);

        $this->service()->costOrder($achat, [
            ['product_id' => $this->ciment->id, 'quantity' => 40, 'cartons' => 4, 'pieces_per_carton' => 10,
                'unit_price_foreign' => 1, 'cbm_per_carton' => 0.1],
        ]);

        $this->assertSame(40, $this->stock($this->entrepot, $this->ciment));
        $this->assertSame(0, $this->stock($this->magasin, $this->ciment), 'Le stock doit entrer à la destination exacte, pas ailleurs.');

        $mouvement = StockMovement::where('source_type', 'purchase_order')->where('source_id', $achat->id)->sole();
        $this->assertSame(StockMovement::PURCHASE, $mouvement->type);
        $this->assertSame(40, (int) $mouvement->quantity);
        $this->assertSame($this->entrepot->id, (int) $mouvement->store_id);
        $this->assertSame($achat->reference, $mouvement->document_ref);
        $this->assertEquals(1_300, $mouvement->unit_price, 'Le mouvement porte le prix de revient atterri.');
        $this->assertSame($this->fournisseur->id, (int) $mouvement->supplier_id);
    }

    public function test_l_achat_met_a_jour_le_prix_de_revient_du_produit(): void
    {
        $achat = $this->achat(['transport_cost_gnf' => 500_000]);

        $this->service()->costOrder($achat, [
            ['product_id' => $this->ciment->id, 'quantity' => 100, 'cartons' => 10, 'pieces_per_carton' => 10,
                'unit_price_foreign' => 2, 'cbm_per_carton' => 0.5],
        ]);

        // (2 × 1300 × 100 + 500 000) / 100 = 7 600
        $this->ciment->refresh();
        $this->assertEquals(7_600, $this->ciment->price_sale, 'price_sale sert de base de coût dans ce modèle.');
        $this->assertEquals(7_600, $this->ciment->coutUnitaire());

        // Le plancher de vente en découle directement.
        [$plancher, $origine] = $this->ciment->plancherDeVente();
        $this->assertEquals(7_600.0, $plancher);
        $this->assertSame('revient', $origine);
    }

    // -----------------------------------------------------------------
    // Modification d'un achat
    // -----------------------------------------------------------------

    /**
     * Changer la destination d'un achat : le stock de la version précédente
     * doit être retiré de l'ANCIENNE destination. Le retirer de la nouvelle
     * faussait deux emplacements d'un coup.
     */
    public function test_changer_la_destination_deplace_le_stock_et_ne_fausse_pas_l_ancienne(): void
    {
        $achat = $this->achat();
        $lignes = [['product_id' => $this->ciment->id, 'quantity' => 30, 'cartons' => 3, 'pieces_per_carton' => 10,
            'unit_price_foreign' => 1, 'cbm_per_carton' => 0.1]];

        $this->service()->costOrder($achat, $lignes);
        $this->assertSame(30, $this->stock($this->entrepot, $this->ciment));

        // L'achat est corrigé : même marchandise, destination différente.
        $ancienneDestination = (int) $achat->store_id;
        $achat->update(['store_id' => $this->magasin->id]);
        $this->service()->costOrder($achat->fresh(), $lignes, $ancienneDestination);

        $this->assertSame(0, $this->stock($this->entrepot, $this->ciment), 'L\'ancienne destination doit être soldée.');
        $this->assertSame(30, $this->stock($this->magasin, $this->ciment));
    }

    public function test_modifier_les_quantites_ne_laisse_pas_les_anciennes_lignes(): void
    {
        $achat = $this->achat();

        $this->service()->costOrder($achat, [
            ['product_id' => $this->ciment->id, 'quantity' => 30, 'unit_price_foreign' => 1],
        ]);
        $this->service()->costOrder($achat->fresh(), [
            ['product_id' => $this->ciment->id, 'quantity' => 12, 'unit_price_foreign' => 1],
        ], (int) $achat->store_id);

        $this->assertCount(1, $achat->load('items')->items);
        $this->assertSame(12, $this->stock($this->entrepot, $this->ciment));
    }

    // -----------------------------------------------------------------
    // Achat financé par un associé
    // -----------------------------------------------------------------

    /**
     * La marchandise d'un associé est suivie à part : son coût ne doit pas
     * écraser le prix de revient des fonds propres, et son stock est rattaché
     * au fonds.
     */
    public function test_un_achat_d_associe_ne_touche_pas_au_prix_des_fonds_propres(): void
    {
        $this->ciment->update(['price_sale' => 5_000, 'price' => 6_500]);

        $fonds = Fund::create(['name' => 'Fonds Diallo', 'associate_name' => 'Diallo']);
        $achat = $this->achat(['fund_id' => $fonds->id, 'transport_cost_gnf' => 0]);

        $this->service()->costOrder($achat, [
            ['product_id' => $this->ciment->id, 'quantity' => 20, 'cartons' => 2, 'pieces_per_carton' => 10,
                'unit_price_foreign' => 10, 'cbm_per_carton' => 0.1],
        ]);

        $this->ciment->refresh();
        $this->assertEquals(5_000, $this->ciment->price_sale, 'Le prix de revient des fonds propres a été écrasé.');
        $this->assertEquals(6_500, $this->ciment->price, 'Le prix de vente a été écrasé par un achat d\'associé.');

        // Le stock et le coût sont rattachés au fonds.
        $this->assertSame(20, $this->stock($this->entrepot, $this->ciment, $fonds->id));
        $this->assertSame(0, $this->stock($this->entrepot, $this->ciment), 'Rien ne doit entrer dans les fonds propres.');
        $this->assertEquals(13_000, app(\App\Services\FundService::class)->unitCost($fonds->id, $this->ciment->id));
    }

    /** Un achat annulé ne compte plus dans l'investissement du fonds. */
    public function test_un_achat_annule_ne_compte_plus_dans_le_fonds(): void
    {
        $fonds = Fund::create(['name' => 'Fonds Bah', 'associate_name' => 'Bah']);
        FundTransaction::create(['fund_id' => $fonds->id, 'type' => FundTransaction::APPORT,
            'amount' => 10_000_000, 'date' => now()->toDateString(), 'user_id' => $this->admin->id]);

        $achat = $this->achat(['fund_id' => $fonds->id]);
        $this->service()->costOrder($achat, [
            ['product_id' => $this->ciment->id, 'quantity' => 10, 'unit_price_foreign' => 100],
        ]);

        $service = app(\App\Services\FundService::class);
        $this->assertEquals(1_300_000, $service->purchasesTotal($fonds->id));

        $achat->fresh()->update(['status' => 'cancelled']);
        $service->forgetCosts();
        $this->assertEquals(0, $service->purchasesTotal($fonds->id));
    }

    // -----------------------------------------------------------------
    // Bout en bout, par l'écran
    // -----------------------------------------------------------------

    public function test_l_ecran_d_achat_enregistre_le_cout_de_revient(): void
    {
        $this->post(route('purchase-orders.store'), [
            'reference' => 'ACH-ECRAN-1',
            'store_id' => $this->entrepot->id,
            'partner_id' => $this->fournisseur->id,
            'origin' => 'guinee',
            'transport_cost_gnf' => 1_000,
            'date_emis' => now()->toDateString(),
            'date_recu' => now()->toDateString(),
            'lines' => [
                ['product_id' => $this->ciment->id, 'quantity' => 10, 'unit_price_foreign' => 1_000],
                ['product_id' => $this->carton->id, 'quantity' => 5, 'unit_price_foreign' => 2_000],
            ],
        ])->assertRedirect()->assertSessionHas('success');

        $achat = PurchaseOrder::where('reference', 'ACH-ECRAN-1')->sole();
        $lignes = $achat->items->keyBy('product_id');

        $this->assertEquals(1_050, $lignes[$this->ciment->id]->landed_unit_cost_gnf);
        $this->assertEquals(2_100, $lignes[$this->carton->id]->landed_unit_cost_gnf);
        $this->assertEquals(21_000, $achat->total_landed_cost_gnf);
        $this->assertSame(10, $this->stock($this->entrepot, $this->ciment));
        $this->assertSame('received', $achat->status);
    }
}
