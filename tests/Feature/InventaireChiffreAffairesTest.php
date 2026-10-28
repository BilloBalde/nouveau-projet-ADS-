<?php

namespace Tests\Feature;

use App\Models\InventorySession;
use App\Models\Partner;
use App\Models\Place;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\User;
use App\Services\InventaireService;
use App\Services\PurchaseOrderCostingService;
use App\Services\StockService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Chiffre d'affaires déduit du stock.
 *
 * Mode de vente d'une boutique qui ne saisit pas ses ventes : elle vend, puis
 * compte le stock restant une fois par mois. Ce qui a disparu entre deux
 * comptages, achats et transferts déduits, a été vendu.
 *
 *   vendu = compté au début + achats + entrants − sortants − pertes + autres
 *           − compté à la fin
 *
 * Les montants attendus sont calculés à la main dans chaque test : une dérive
 * du calcul doit se voir sur le chiffre, pas seulement sur un total.
 */
class InventaireChiffreAffairesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Store $madina;

    private Store $kaloum;

    private Product $ciment;

    private Product $sucre;

    protected function setUp(): void
    {
        parent::setUp();

        // Les scénarios de comptage portent des dates fixes. Sans horloge
        // figée, elles basculent dans le futur avec le temps qui passe — et un
        // comptage daté du futur est désormais refusé, à juste titre.
        $this->travelTo('2027-12-31 12:00:00');

        $this->seed(RolesSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->admin = User::create([
            'name' => 'Patron', 'email' => 'p'.uniqid().'@example.com', 'username' => 'p'.uniqid(),
            'password' => Hash::make('password'), 'role_id' => User::ROLE_ADMIN,
            'phone' => '620', 'status' => 'Active', 'token' => '',
        ]);

        Place::create(['placeName' => 'Conakry', 'countryName' => 'Guinée', 'description' => 'test']);
        $this->madina = $this->magasin('Madina', true);
        $this->kaloum = $this->magasin('Kaloum');

        // Prix de vente : 50 000 le sac de ciment, 12 000 le carton de sucre.
        // price_manuel : dans ce mode le gérant fixe ses prix, et un achat ne
        // doit pas les recalculer — ils sont la seule source du chiffre
        // d'affaires (voir test_un_achat_ne_doit_pas_reecrire_le_prix_qui_valorise_le_ca).
        $this->ciment = Product::create(['libelle' => 'Ciment', 'sku' => 'CIM', 'description' => 'x',
            'unite' => 'pcs', 'price' => 50_000, 'price_sale' => 38_000, 'price_manuel' => true]);
        $this->sucre = Product::create(['libelle' => 'Sucre', 'sku' => 'SUC', 'description' => 'x',
            'unite' => 'carton', 'price' => 12_000, 'price_sale' => 9_000, 'price_manuel' => true]);

        $this->actingAs($this->admin);
    }

    private function magasin(string $nom, bool $principal = false): Store
    {
        return Store::create(['store_name' => $nom, 'place_id' => Place::firstOrFail()->id, 'user_id' => $this->admin->id,
            'store_picture' => '', 'description' => 'x', 'status' => 1, 'type' => 'magasin', 'is_main' => $principal]);
    }

    private function service(): InventaireService
    {
        return app(InventaireService::class);
    }

    private function stock(Store $magasin, Product $produit): int
    {
        return (int) StoreProduct::where('store_id', $magasin->id)
            ->where('product_id', $produit->id)
            ->whereNull('fund_id')
            ->sum('quantity');
    }

    /** Comptage initial : pose le point de départ, ne déduit aucune vente. */
    private function comptageInitial(Store $magasin, array $quantites, string $date): InventorySession
    {
        $seance = $this->service()->preparer($magasin->id, $date);

        return $this->service()->valider($seance, collect($quantites)
            ->map(fn ($qte, $sku) => ['product_id' => Product::where('sku', $sku)->value('id'), 'counted' => $qte])
            ->values()->all());
    }

    /** Achat livré dans une boutique, à une date donnée. */
    private function achat(Store $magasin, Product $produit, int $quantite, string $date): void
    {
        $achat = PurchaseOrder::create([
            'reference' => 'ACH-'.uniqid(), 'store_id' => $magasin->id,
            'partner_id' => Partner::firstOrCreate(['name' => 'Fournisseur'])->id,
            'created_by' => $this->admin->id, 'origin' => 'guinee', 'exchange_rate_used' => 1,
            'transport_cost_gnf' => 0, 'customs_cost_gnf' => 0, 'other_fees_gnf' => 0,
            'status' => 'received', 'date_emis' => $date,
        ]);

        app(PurchaseOrderCostingService::class)->costOrder($achat, [
            ['product_id' => $produit->id, 'quantity' => $quantite, 'unit_price_foreign' => 1_000],
        ]);

        // Le mouvement est daté à la création : on le replace dans la période.
        StockMovement::where('source_type', 'purchase_order')->where('source_id', $achat->id)
            ->update(['created_at' => Carbon::parse($date)]);
    }

    // -----------------------------------------------------------------

    /**
     * Le scénario du client, bout en bout.
     *
     * 1er janvier : comptage initial, 100 sacs de ciment en boutique.
     * Au cours du mois : 50 sacs achetés et livrés.
     * 31 janvier : il reste 30 sacs comptés.
     *
     *   vendu = 100 + 50 − 30 = 120 sacs
     *   chiffre d'affaires = 120 × 50 000 = 6 000 000 GNF
     */
    public function test_le_chiffre_d_affaires_du_mois_se_deduit_du_comptage(): void
    {
        $this->comptageInitial($this->madina, ['CIM' => 100], '2026-01-01');
        $this->achat($this->madina, $this->ciment, 50, '2026-01-15');

        $seance = $this->service()->preparer($this->madina->id, '2026-01-31');
        $seance = $this->service()->valider($seance, [
            ['product_id' => $this->ciment->id, 'counted' => 30],
        ]);

        $ligne = $seance->lines->sole();

        $this->assertSame(100, (int) $ligne->opening_quantity, 'Compté au comptage précédent');
        $this->assertSame(50, (int) $ligne->purchased_quantity, 'Acheté pendant la période');
        $this->assertSame(30, (int) $ligne->counted_quantity, 'Compté à la fin');
        $this->assertSame(120, (int) $ligne->sold_quantity, '100 + 50 − 30');
        $this->assertEquals(6_000_000, $ligne->revenue_gnf);
        $this->assertEquals(6_000_000, $seance->total_revenue_gnf);
        $this->assertTrue($ligne->calculCoherent(), 'Les termes doivent reconstituer la quantité vendue.');

        // Le stock enregistré suit le comptage.
        $this->assertSame(30, $this->stock($this->madina, $this->ciment));
    }

    /** Plusieurs produits, chacun à son prix et dans son unité. */
    public function test_plusieurs_produits_sont_totalises_chacun_a_son_prix(): void
    {
        $this->comptageInitial($this->madina, ['CIM' => 40, 'SUC' => 20], '2026-02-01');

        $seance = $this->service()->preparer($this->madina->id, '2026-02-28');
        $seance = $this->service()->valider($seance, [
            ['product_id' => $this->ciment->id, 'counted' => 10],  // 30 vendus × 50 000
            ['product_id' => $this->sucre->id, 'counted' => 5],    // 15 vendus × 12 000
        ]);

        $lignes = $seance->lines->keyBy('product_id');
        $this->assertSame(30, (int) $lignes[$this->ciment->id]->sold_quantity);
        $this->assertSame(15, (int) $lignes[$this->sucre->id]->sold_quantity);
        $this->assertEquals(1_500_000, $lignes[$this->ciment->id]->revenue_gnf);
        $this->assertEquals(180_000, $lignes[$this->sucre->id]->revenue_gnf);
        $this->assertEquals(1_680_000, $seance->total_revenue_gnf);

        // L'unité du produit est figée sur la ligne.
        $this->assertSame('carton', $lignes[$this->sucre->id]->unite);
        $this->assertSame('15 cartons', $lignes[$this->sucre->id]->quantite_vendue_affichee);
    }

    /**
     * Une perte déclarée sort du stock mais pas du chiffre d'affaires. Sans
     * cette distinction, la casse est facturée au prix de vente.
     */
    public function test_une_perte_declaree_ne_compte_pas_comme_vendue(): void
    {
        $this->comptageInitial($this->madina, ['SUC' => 50], '2026-03-01');

        $seance = $this->service()->preparer($this->madina->id, '2026-03-31');
        $seance = $this->service()->valider($seance, [
            ['product_id' => $this->sucre->id, 'counted' => 20, 'loss' => 5, 'loss_reason' => 'Cartons mouillés'],
        ]);

        $ligne = $seance->lines->sole();

        // 50 − 5 perdus − 20 restants = 25 vendus, et non 30.
        $this->assertSame(25, (int) $ligne->sold_quantity);
        $this->assertSame(5, (int) $ligne->loss_quantity);
        $this->assertEquals(300_000, $ligne->revenue_gnf, '25 × 12 000');
        // Une perte se chiffre à ce qu'elle a coûté (9 000), pas à ce qu'elle
        // aurait rapporté : la marchandise perdue n'a jamais été vendue, et
        // compter le prix de vente mélangerait une perte réelle avec un
        // manque à gagner.
        $this->assertEquals(45_000, $seance->total_loss_gnf, '5 × 9 000 de prix de revient, hors chiffre d\'affaires');
        $this->assertTrue($ligne->calculCoherent());

        // Le journal distingue la vente de la perte.
        $mouvements = StockMovement::where('source_type', InventaireService::ORIGINE)
            ->where('source_id', $seance->id)->get()->groupBy('type');
        $this->assertSame(-25, (int) $mouvements[StockMovement::SALE]->sum('quantity'));
        $this->assertSame(-5, (int) $mouvements[StockMovement::LOSS]->sum('quantity'));
        $this->assertSame('Cartons mouillés', $mouvements[StockMovement::LOSS]->first()->reason);

        $this->assertSame(20, $this->stock($this->madina, $this->sucre));
    }

    /** Les transferts entre boutiques entrent dans le calcul des deux côtés. */
    public function test_les_transferts_entrent_dans_le_calcul(): void
    {
        $this->comptageInitial($this->madina, ['CIM' => 60], '2026-04-01');
        $this->comptageInitial($this->kaloum, ['CIM' => 0], '2026-04-01');

        // 20 sacs partent de Madina vers Kaloum le 10 avril.
        $stock = app(StockService::class);
        $stock->forceDecrement($this->madina->id, $this->ciment->id, 20, [
            'type' => StockMovement::TRANSFER_OUT, 'counterpart_store_id' => $this->kaloum->id,
        ]);
        $stock->increment($this->kaloum->id, $this->ciment->id, 20, [
            'type' => StockMovement::TRANSFER_IN, 'counterpart_store_id' => $this->madina->id,
        ]);
        StockMovement::whereIn('type', [StockMovement::TRANSFER_OUT, StockMovement::TRANSFER_IN])
            ->update(['created_at' => Carbon::parse('2026-04-10')]);

        // Madina : 60 − 20 transférés − 25 restants = 15 vendus.
        $madina = $this->service()->preparer($this->madina->id, '2026-04-30');
        $madina = $this->service()->valider($madina, [['product_id' => $this->ciment->id, 'counted' => 25]]);
        $ligneMadina = $madina->lines->sole();

        $this->assertSame(20, (int) $ligneMadina->transfer_out_quantity);
        $this->assertSame(15, (int) $ligneMadina->sold_quantity, '60 − 20 − 25');
        $this->assertEquals(750_000, $ligneMadina->revenue_gnf);

        // Kaloum : 0 + 20 reçus − 8 restants = 12 vendus.
        $kaloum = $this->service()->preparer($this->kaloum->id, '2026-04-30');
        $kaloum = $this->service()->valider($kaloum, [['product_id' => $this->ciment->id, 'counted' => 8]]);
        $ligneKaloum = $kaloum->lines->sole();

        $this->assertSame(20, (int) $ligneKaloum->transfer_in_quantity);
        $this->assertSame(12, (int) $ligneKaloum->sold_quantity, '0 + 20 − 8');
        $this->assertEquals(600_000, $ligneKaloum->revenue_gnf);
    }

    /**
     * Le premier comptage d'une boutique ne déduit aucune vente : il n'y a pas
     * de borne de début. Sans ce garde-fou, tout le stock compté serait pris
     * pour une vente du néant.
     */
    public function test_le_premier_comptage_ne_deduit_aucune_vente(): void
    {
        $seance = $this->service()->preparer($this->madina->id, '2026-05-01');
        $seance = $this->service()->valider($seance, [
            ['product_id' => $this->ciment->id, 'counted' => 80],
        ]);

        $this->assertTrue($seance->estPremiereSeance());
        $this->assertSame(0, (int) $seance->lines->sole()->sold_quantity);
        $this->assertEquals(0, $seance->total_revenue_gnf);
        $this->assertSame(80, $this->stock($this->madina, $this->ciment), 'Le comptage pose le stock de départ.');
    }

    /**
     * Le chiffre d'affaires d'une période déjà établie ne doit plus bouger,
     * même si le prix du produit change ensuite — un achat le recalcule
     * automatiquement.
     */
    public function test_un_chiffre_d_affaires_etabli_ne_bouge_plus_quand_le_prix_change(): void
    {
        $this->comptageInitial($this->madina, ['CIM' => 10], '2026-06-01');

        $seance = $this->service()->preparer($this->madina->id, '2026-06-30');
        $seance = $this->service()->valider($seance, [['product_id' => $this->ciment->id, 'counted' => 4]]);

        $this->assertEquals(300_000, $seance->total_revenue_gnf, '6 × 50 000');

        // Le prix double après coup.
        $this->ciment->update(['price' => 100_000]);

        $this->assertEquals(50_000, $seance->fresh('lines')->lines->sole()->unit_price_gnf, 'Le prix figé ne suit pas la fiche.');
        $this->assertEquals(300_000, $seance->fresh()->total_revenue_gnf);
    }

    /**
     * Une séance validée en retard ne doit pas polluer la période suivante :
     * ses propres mouvements sont des résultats de comptage, pas des entrées.
     */
    public function test_les_mouvements_d_une_seance_ne_nourrissent_pas_la_suivante(): void
    {
        $this->comptageInitial($this->madina, ['CIM' => 100], '2026-07-01');

        $juillet = $this->service()->preparer($this->madina->id, '2026-07-31');
        $juillet = $this->service()->valider($juillet, [['product_id' => $this->ciment->id, 'counted' => 60]]);
        $this->assertSame(40, (int) $juillet->lines->sole()->sold_quantity);

        // Août : aucun achat, 50 comptés. 60 − 50 = 10 vendus, et pas davantage.
        $aout = $this->service()->preparer($this->madina->id, '2026-08-31');
        $aout = $this->service()->valider($aout, [['product_id' => $this->ciment->id, 'counted' => 50]]);

        $ligne = $aout->lines->sole();
        $this->assertSame(60, (int) $ligne->opening_quantity);
        $this->assertSame(0, (int) $ligne->other_quantity, 'Les mouvements de la séance de juillet ne doivent pas apparaître ici.');
        $this->assertSame(10, (int) $ligne->sold_quantity);
    }

    /**
     * Un ajustement manuel du stock est porté à part, jamais fondu dans les
     * ventes : sinon une correction passerait pour du chiffre d'affaires.
     */
    public function test_un_ajustement_manuel_est_porte_a_part(): void
    {
        $this->comptageInitial($this->madina, ['CIM' => 100], '2026-09-01');

        // Le magasinier retrouve 5 sacs oubliés en réserve.
        app(StockService::class)->increment($this->madina->id, $this->ciment->id, 5, [
            'type' => StockMovement::ADJUSTMENT, 'reason' => 'Sacs retrouvés en réserve',
        ]);
        StockMovement::where('type', StockMovement::ADJUSTMENT)->update(['created_at' => Carbon::parse('2026-09-10')]);

        $seance = $this->service()->preparer($this->madina->id, '2026-09-30');
        $seance = $this->service()->valider($seance, [['product_id' => $this->ciment->id, 'counted' => 70]]);

        $ligne = $seance->lines->sole();
        $this->assertSame(5, (int) $ligne->other_quantity);
        $this->assertSame(35, (int) $ligne->sold_quantity, '100 + 5 − 70');
        $this->assertTrue($ligne->calculCoherent());
    }

    // -----------------------------------------------------------------
    // Garde-fous
    // -----------------------------------------------------------------

    public function test_deux_comptages_en_cours_sont_refuses_pour_une_meme_boutique(): void
    {
        $this->service()->preparer($this->madina->id, '2026-10-01');

        $this->expectExceptionMessage('Un comptage est déjà en cours');
        $this->service()->preparer($this->madina->id, '2026-10-02');
    }

    public function test_un_comptage_anterieur_au_dernier_est_refuse(): void
    {
        $this->comptageInitial($this->madina, ['CIM' => 10], '2026-10-15');

        $this->expectExceptionMessage('ne peut pas porter une date antérieure');
        $this->service()->preparer($this->madina->id, '2026-10-01');
    }

    public function test_une_perte_superieure_au_stock_theorique_est_refusee(): void
    {
        $this->comptageInitial($this->madina, ['CIM' => 10], '2026-11-01');
        $seance = $this->service()->preparer($this->madina->id, '2026-11-30');

        $this->expectExceptionMessage('Perte déclarée impossible');
        $this->service()->valider($seance, [
            ['product_id' => $this->ciment->id, 'counted' => 0, 'loss' => 20],
        ]);
    }

    public function test_une_seance_validee_ne_se_revalide_pas(): void
    {
        $seance = $this->comptageInitial($this->madina, ['CIM' => 10], '2026-12-01');

        $this->expectExceptionMessage('déjà été validée');
        $this->service()->valider($seance, [['product_id' => $this->ciment->id, 'counted' => 5]]);
    }

    /**
     * Un stock compté supérieur au théorique ne crée pas une vente négative :
     * l'excédent est un écart, pas un chiffre d'affaires en moins.
     */
    public function test_un_excedent_ne_produit_pas_de_vente_negative(): void
    {
        $this->comptageInitial($this->madina, ['CIM' => 10], '2027-01-01');

        $seance = $this->service()->preparer($this->madina->id, '2027-01-31');
        $seance = $this->service()->valider($seance, [['product_id' => $this->ciment->id, 'counted' => 15]]);

        $this->assertSame(0, (int) $seance->lines->sole()->sold_quantity);
        $this->assertEquals(0, $seance->total_revenue_gnf);
        $this->assertSame(15, $this->stock($this->madina, $this->ciment), 'Le stock suit tout de même le comptage.');
    }

    /**
     * Mise en garde sur ce mode de vente : le prix de vente est la SEULE
     * source du chiffre d'affaires. Or un achat recalcule ce prix (prix de
     * revient + marge) pour tout produit qui n'a pas de prix fixé à la main.
     *
     * Un achat au milieu du mois réécrirait donc le prix qui valorise la
     * période, sans que personne le demande. Avec un prix fixé à la main, il
     * ne bouge pas — c'est le réglage à retenir pour ce client.
     */
    public function test_un_achat_ne_doit_pas_reecrire_le_prix_qui_valorise_le_ca(): void
    {
        // Produit dont le prix suit le coût : vulnérable.
        $suit = Product::create(['libelle' => 'Riz', 'sku' => 'RIZ', 'description' => 'x',
            'unite' => 'carton', 'price' => 80_000, 'price_sale' => 60_000, 'price_manuel' => false]);

        $this->comptageInitial($this->madina, ['RIZ' => 10, 'CIM' => 10], '2027-02-01');
        $this->achat($this->madina, $suit, 5, '2027-02-10');          // coût 1 000 → prix 1 300
        $this->achat($this->madina, $this->ciment, 5, '2027-02-10');  // prix fixé à la main : inchangé

        $this->assertEquals(1_300, $suit->fresh()->price, 'Le prix a suivi le dernier coût d\'achat.');
        $this->assertEquals(50_000, $this->ciment->fresh()->price, 'Le prix fixé à la main tient.');

        $seance = $this->service()->preparer($this->madina->id, '2027-02-28');
        $seance = $this->service()->valider($seance, [
            ['product_id' => $suit->id, 'counted' => 5],          // 10 vendus
            ['product_id' => $this->ciment->id, 'counted' => 5],  // 10 vendus
        ]);

        $lignes = $seance->lines->keyBy('product_id');
        $this->assertEquals(13_000, $lignes[$suit->id]->revenue_gnf, '10 × 1 300 : le chiffre d\'affaires a fondu.');
        $this->assertEquals(500_000, $lignes[$this->ciment->id]->revenue_gnf, '10 × 50 000 : le prix voulu.');
    }

    /**
     * Le prix peut aussi être corrigé au moment du comptage : c'est lui qui
     * est figé, pas celui de la fiche.
     */
    public function test_le_prix_peut_etre_corrige_au_comptage(): void
    {
        $this->comptageInitial($this->madina, ['CIM' => 20], '2027-03-01');

        $seance = $this->service()->preparer($this->madina->id, '2027-03-31');
        $seance = $this->service()->valider($seance, [
            ['product_id' => $this->ciment->id, 'counted' => 5, 'unit_price' => 55_000],
        ]);

        $ligne = $seance->lines->sole();
        $this->assertEquals(55_000, $ligne->unit_price_gnf);
        $this->assertEquals(825_000, $ligne->revenue_gnf, '15 × 55 000');
        $this->assertEquals(50_000, $this->ciment->fresh()->price, 'La fiche produit n\'est pas modifiée par le comptage.');
    }

    /**
     * La marge est figée au comptage, comme le prix : un achat postérieur
     * change le prix de revient du produit, et la marge d'une période close
     * ne doit pas bouger derrière.
     */
    public function test_la_marge_est_calculee_et_figee(): void
    {
        // Revient 38 000, vente 50 000 → 12 000 de marge par sac.
        $this->comptageInitial($this->madina, ['CIM' => 50], '2027-04-01');

        $seance = $this->service()->preparer($this->madina->id, '2027-04-30');
        $seance = $this->service()->valider($seance, [['product_id' => $this->ciment->id, 'counted' => 20]]);

        $ligne = $seance->lines->sole();
        $this->assertEquals(38_000, $ligne->unit_cost_gnf);
        $this->assertEquals(360_000, $ligne->margin_gnf, '30 vendus × (50 000 − 38 000)');
        $this->assertEquals(360_000, $seance->total_margin_gnf);

        // Le prix de revient du produit change ensuite : la marge ne bouge pas.
        $this->ciment->update(['price_sale' => 49_000]);
        $this->assertEquals(360_000, $seance->fresh()->total_margin_gnf);
    }

    /** Sans coût connu, pas de marge inventée. */
    public function test_sans_prix_de_revient_la_marge_reste_a_zero(): void
    {
        $sansCout = Product::create(['libelle' => 'Don', 'sku' => 'DON', 'description' => 'x',
            'unite' => 'pcs', 'price' => 10_000, 'price_sale' => 0, 'price_manuel' => true]);
        StoreProduct::create(['store_id' => $this->madina->id, 'product_id' => $sansCout->id, 'quantity' => 10]);

        $this->comptageInitial($this->madina, ['DON' => 10], '2027-05-01');
        $seance = $this->service()->preparer($this->madina->id, '2027-05-31');
        $seance = $this->service()->valider($seance, [['product_id' => $sansCout->id, 'counted' => 4]]);

        $ligne = $seance->lines->sole();
        $this->assertEquals(60_000, $ligne->revenue_gnf, '6 × 10 000 : le chiffre d\'affaires, lui, est connu');
        $this->assertEquals(0, $ligne->margin_gnf, 'Aucune marge inventée faute de prix de revient.');
    }
}
