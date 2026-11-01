<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\DeliveryAddress;
use App\Models\Devi;
use App\Models\DeviLine;
use App\Models\Facture;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Place;
use App\Models\Product;
use App\Models\PurchaseOrderItem;
use App\Models\Sale;
use App\Models\StockMovement;
use App\Models\StockTransfer;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\TransactionFacture;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Tout se compte à la pièce, et il n'y a pas de promotion : la fiche
 * produit, la caisse, les devis, les achats, les transferts, les comptages,
 * les rapports et les bons parlent tous en pièces.
 */
class PiecesSeulementTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Store $magasin;

    private Customer $client;

    private Category $categorie;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->admin = User::create([
            'name' => 'Patron', 'email' => 'patron@example.com', 'username' => 'patron',
            'password' => Hash::make('password'), 'role_id' => User::ROLE_ADMIN,
            'phone' => '620', 'status' => 'Active', 'token' => '',
        ]);
        $lieu = Place::create(['placeName' => 'Conakry', 'countryName' => 'Guinée', 'description' => 'test']);
        $this->magasin = Store::create(['store_name' => 'Madina', 'place_id' => $lieu->id, 'user_id' => $this->admin->id,
            'store_picture' => '', 'description' => 'x', 'status' => 1, 'type' => 'magasin', 'is_main' => true]);
        $this->client = Customer::create(['customerName' => 'Client X', 'mark' => 'CLX', 'tel' => '620', 'address' => 'Madina']);
        $this->categorie = Category::create(['slug' => 'quincaillerie', 'category_type' => 'Quincaillerie', 'description' => 'x']);
    }

    private function creerProduit(array $unites): TestResponse
    {
        return $this->actingAs($this->admin)->post(route('produits.store'), [
            'categories' => [$this->categorie->id], 'libelle' => 'Vis 6 mm', 'sku' => 'VIS-'.uniqid(),
            'description' => 'x', 'price_sale' => 100, 'stock_initial' => 500, 'store_id' => $this->magasin->id,
        ] + $unites);
    }

    private function vendre(Product $produit, int $qte, float $prix)
    {
        return $this->actingAs($this->admin)->post(route('sales.store'), [
            'numeroFacture' => 'F-'.uniqid(), 'store_id' => $this->magasin->id, 'customer_id' => $this->client->id,
            'avance' => 0, 'paid_by' => 'cash',
            'sales' => [1 => ['product_id' => $produit->id, 'quantity' => $qte, 'prix' => $prix,
                'store_id' => $this->magasin->id, 'is_third_party' => '0']],
        ]);
    }

    public function test_tout_produit_est_a_la_piece_quelle_que_soit_la_saisie(): void
    {
        // Un formulaire ancien qui enverrait encore une unité ou une promo :
        // rien n'est retenu.
        $this->creerProduit(['unite' => 'carton', 'pcs_par_carton' => 24, 'is_promo' => 1, 'promo_percent' => 10])
            ->assertSessionHasNoErrors()->assertRedirect(route('produits.index'));

        $p = Product::firstOrFail();
        $this->assertSame('pcs', $p->getRawOriginal('unite'));
        $this->assertNull($p->pcs_par_carton);
        $this->assertFalse((bool) $p->is_promo);
        $this->assertFalse($p->isPromoActive());
        $this->assertSame('Pièce', $p->unite_libelle);
        $this->assertSame('500 pièces', $p->quantiteAvecUnite(500));
        $this->assertSame(500, (int) StoreProduct::where('product_id', $p->id)->value('quantity'));
    }

    public function test_les_formulaires_ne_proposent_ni_unite_ni_promotion(): void
    {
        $this->actingAs($this->admin)->get(route('produits.create'))->assertOk()
            ->assertDontSee('name="unite"', false)->assertDontSee('name="is_promo"', false)
            ->assertSee('Prix de Revient par pièce');

        $this->creerProduit([]);
        $p = Product::firstOrFail();
        $this->actingAs($this->admin)->get(route('produits.edit', $p->id))->assertOk()
            ->assertDontSee('name="unite"', false)->assertDontSee('name="is_promo"', false);
        $this->actingAs($this->admin)->get(route('purchase-orders.create'))->assertOk()
            ->assertDontSee('newProductUnite', false);
    }

    public function test_une_promo_ancienne_ne_baisse_plus_aucun_prix(): void
    {
        $p = Product::create(['libelle' => 'Pelle', 'sku' => 'PEL', 'description' => 'x', 'price' => 1000, 'price_sale' => 700,
            'is_promo' => true, 'promo_price' => 500, 'promo_percent' => 50]);

        $this->assertFalse($p->isPromoActive());
        $this->assertNull($p->effective_promo_price);
    }

    public function test_la_migration_convertit_douzaines_et_cartons_en_pieces(): void
    {
        $douzaine = Product::create(['libelle' => 'Pinceau', 'sku' => 'PIN', 'description' => 'x', 'price' => 12_000, 'price_sale' => 6_000]);
        $carton = Product::create(['libelle' => 'Vis', 'sku' => 'VIS', 'description' => 'x', 'price' => 2_400, 'price_sale' => 1_200]);
        DB::table('products')->where('id', $douzaine->id)->update(['unite' => 'douzaine', 'is_promo' => true, 'promo_price' => 10_000]);
        DB::table('products')->where('id', $carton->id)->update(['unite' => 'carton', 'pcs_par_carton' => 24]);
        StoreProduct::create(['store_id' => $this->magasin->id, 'product_id' => $douzaine->id, 'quantity' => 3]);
        StoreProduct::create(['store_id' => $this->magasin->id, 'product_id' => $carton->id, 'quantity' => 2]);
        $vente = Sale::create(['numeroFacture' => 'F-OLD', 'product_id' => $douzaine->id, 'store_id' => $this->magasin->id,
            'user_id' => $this->admin->id, 'quantity' => 2, 'unite' => 'douzaine', 'prix' => 12_000, 'prixTotal' => 24_000, 'interet' => 12_000]);

        (require database_path('migrations/2026_11_01_000001_convertit_tout_en_pieces_et_retire_les_promotions.php'))->up();

        $douzaine->refresh();
        $this->assertSame('pcs', $douzaine->getRawOriginal('unite'));
        $this->assertEquals(1_000, (float) $douzaine->price);
        $this->assertEquals(500, (float) $douzaine->price_sale);
        $this->assertFalse((bool) $douzaine->is_promo);
        $this->assertSame(36, (int) StoreProduct::where('product_id', $douzaine->id)->value('quantity'));
        $this->assertSame(48, (int) StoreProduct::where('product_id', $carton->id)->value('quantity'));
        $this->assertEquals(50, (float) $carton->fresh()->price_sale);

        // Le montant d'une vente passée ne bouge pas : 24 pièces à 1 000.
        $vente->refresh();
        $this->assertSame(24, (int) $vente->quantity);
        $this->assertEquals(1_000, (float) $vente->prix);
        $this->assertEquals(24_000, (float) $vente->prixTotal);
        $this->assertSame('pcs', $vente->unite);
    }

    public function test_vente_refusee_si_stock_insuffisant_ou_a_perte(): void
    {
        $this->creerProduit([]);
        $p = Product::firstOrFail();

        $this->vendre($p, 501, 3000)->assertSessionHas('error');
        $this->vendre($p, 1, 90)->assertSessionHas('error');
        $this->assertSame(0, Sale::count());
        $this->assertSame(500, (int) StoreProduct::where('product_id', $p->id)->value('quantity'));
    }

    public function test_produit_sans_unite_choisie_reste_a_la_piece(): void
    {
        $p = Product::create(['libelle' => 'Ancien', 'sku' => 'OLD', 'description' => 'x', 'price_sale' => 10, 'price' => 13]);
        $this->assertSame('pcs', $p->fresh()->unite);
        $this->assertSame('3 pièces', $p->fresh()->quantiteAvecUnite(3));
    }

    public function test_panier_et_commande_en_ligne_a_la_piece(): void
    {
        $this->creerProduit([]);
        $p = Product::firstOrFail();
        $client = User::create([
            'name' => 'Client web', 'email' => 'web@example.com', 'username' => 'web',
            'password' => Hash::make('password'), 'role_id' => User::ROLE_CUSTOMER,
            'phone' => '621', 'status' => 'Active', 'token' => '',
        ]);
        $adresse = DeliveryAddress::create(['user_id' => $client->id, 'full_address' => 'Madina', 'city' => 'Conakry', 'phone' => '621']);

        $this->postJson(route('cart.add', $p->id), ['quantity' => 3])->assertOk();
        $this->assertSame('pcs', session('cart')[$p->id]['unite']);
        $this->get(route('panier'))->assertOk()->assertSee('3 pièces')->assertSee('/ pièce');

        $this->actingAs($client)->post(route('orders.store'), [
            'delivery_address_id' => $adresse->id, 'payment_method' => 'cod',
            'items' => [['product_id' => $p->id, 'quantity' => 3]],
        ])->assertRedirect();

        $ligne = OrderItem::firstOrFail();
        $this->assertSame('pcs', $ligne->unite);
        $this->assertSame('3 pièces', $ligne->quantite_affichee);

        // L'unité reste figée sur la commande si la fiche change ensuite.
        $p->update(['unite' => 'pcs']);
        $this->actingAs($client)->get(route('orders.show', $ligne->order_id))->assertOk()->assertSee('3 pièces');
    }

    public function test_devis_a_la_piece(): void
    {
        $this->creerProduit([]);
        $p = Product::firstOrFail();

        $this->actingAs($this->admin)->get(route('devis.create'))->assertOk()->assertSee($p->libelle);

        $devis = Devi::create(['store_id' => $this->magasin->id, 'customer_id' => $this->client->id, 'total_amount' => 0, 'status' => 'accepted', 'created_by' => $this->admin->id]);
        $ligne = DeviLine::create(['devis_id' => $devis->id, 'product_id' => $p->id, 'quantity' => 4, 'unit_price' => 150, 'total_price' => 600]);
        $this->assertSame('pcs', $ligne->unite);

        $this->actingAs($this->admin)->get(route('devis.show', $devis->id))->assertOk()->assertSee('4 pièces');

        // Devis validé : la vente garde l'unité.
        $this->actingAs($this->admin)->post(route('devis.validate', $devis->id))->assertRedirect();
        $this->assertSame('4 pièces', Sale::firstOrFail()->quantite_affichee);
    }

    public function test_facturation_manuelle_a_la_piece(): void
    {
        $this->creerProduit([]);
        $p = Product::firstOrFail();

        // Le formulaire propose l'unité et le prix du produit.
        $this->actingAs($this->admin)->getJson('/fetch-product-details?exact=1&productName='.urlencode($p->libelle))
            ->assertJson(['success' => true, 'product' => ['unite' => 'Pièce', 'price' => 130]]);
        $this->actingAs($this->admin)->getJson('/fetch-product-details?exact=1&productName=Vis')
            ->assertJson(['success' => false]);
        $this->actingAs($this->admin)->getJson('/fetch-product-details-suggestion?suggestions=1&productName=Vis')
            ->assertJsonPath('suggestions.0.unite', 'Pièce');

        // 3 pièces à 3 000 : qty_per_ctn et sous-total envoyés par le
        // navigateur sont ignorés, tout est recalculé dans l'unité du produit.
        $this->actingAs($this->admin)->post(route('facturations.store'), [
            'numeroFacture' => 'FAC-U1', 'avance' => 0, 'customer_id' => $this->client->id, 'store_id' => $this->magasin->id,
            'products' => [1 => ['productName' => $p->libelle, 'cartons' => 3, 'qty_per_ctn' => 24, 'price' => 3000, 'subtotal' => 1]],
        ])->assertSessionHas('success');

        $vente = Sale::where('numeroFacture', 'FAC-U1')->firstOrFail();
        $this->assertSame(3, (int) $vente->quantity);
        $this->assertSame('3 pièces', $vente->quantite_affichee);
        $this->assertEquals(9000, $vente->prixTotal);
        $this->assertEquals(9000, Facture::where('numero_facture', 'FAC-U1')->value('montant_total'));
        $this->assertSame(497, (int) StoreProduct::where('product_id', $p->id)->value('quantity'));

        $this->actingAs($this->admin)->get(route('factures.show', 'FAC-U1'))->assertOk()->assertSee('3 pièces');
    }

    public function test_achat_a_la_piece(): void
    {
        $this->creerProduit([]);
        $p = Product::firstOrFail();

        // Le formulaire d'achat présente l'unité de chaque produit.
        $this->actingAs($this->admin)->get(route('purchase-orders.create'))->assertOk()
            ->assertSee('data-unite="pcs"', false)->assertSee('Pièce');

        // 40 pièces à 2 000 le carton : 40 pièces entrent en stock.
        $this->actingAs($this->admin)->post(route('purchase-orders.store'), [
            'reference' => 'PO-U1', 'store_id' => $this->magasin->id, 'origin' => 'guinee',
            'date_emis' => now()->toDateString(), 'date_recu' => now()->toDateString(),
            'lines' => [['product_id' => $p->id, 'quantity' => 40, 'unit_price_foreign' => 2000]],
        ])->assertSessionHasNoErrors();

        $ligne = PurchaseOrderItem::firstOrFail();
        $this->assertSame('pcs', $ligne->unite);
        $this->assertSame('40 pièces', $ligne->quantite_affichee);
        $this->assertSame(540, (int) StoreProduct::where('product_id', $p->id)->value('quantity'));
        $this->assertEquals(2000, (float) $p->fresh()->price_sale); // prix de revient par carton

        $this->actingAs($this->admin)->get(route('purchase-orders.show', $ligne->purchase_order_id))
            ->assertOk()->assertSee('40 pièces')->assertSee('/ pièce');
    }

    public function test_creation_rapide_de_produit_depuis_un_achat_a_la_piece(): void
    {
        $base = ['libelle' => 'Câble 2,5', 'sku' => 'CAB-25', 'category_id' => $this->categorie->id];

        // Une unité envoyée par un ancien formulaire est ignorée.
        $this->actingAs($this->admin)->postJson(route('produits.quickCreate'), $base + ['unite' => 'rouleau'])
            ->assertOk()->assertJson(['success' => true, 'product' => [
                'unite' => 'pcs', 'unite_libelle' => 'Pièce', 'unite_pluriel' => 'pièces',
            ]]);
        $this->assertSame('pcs', Product::where('sku', 'CAB-25')->value('unite'));
    }

    public function test_transfert_a_la_piece(): void
    {
        $this->creerProduit([]);
        $p = Product::firstOrFail();
        $entrepot = Store::create(['store_name' => 'Entrepôt Madina', 'place_id' => $this->magasin->place_id, 'user_id' => $this->admin->id,
            'store_picture' => '', 'description' => 'x', 'status' => 1, 'type' => 'entrepot', 'parent_id' => $this->magasin->id]);

        // Le formulaire de transfert présente l'unité de chaque produit.
        $this->actingAs($this->admin)->get(route('transfers.index'))->assertOk()
            ->assertSee('Pièce')->assertSee('data-unite-pluriel="pièces"', false);

        // 12 pièces du magasin vers l'entrepôt, exécutés tout de suite.
        $this->actingAs($this->admin)->post(route('transfers.store'), [
            'product_id' => $p->id, 'from_store_id' => $this->magasin->id, 'to_store_id' => $entrepot->id,
            'quantity' => 12, 'execute_now' => 1,
        ])->assertSessionHas('success');

        $transfert = StockTransfer::firstOrFail();
        $this->assertSame('pcs', $transfert->unite);
        $this->assertSame('12 pièces', $transfert->quantite_affichee);
        $this->assertSame(488, (int) StoreProduct::where('product_id', $p->id)->where('store_id', $this->magasin->id)->value('quantity'));
        $this->assertSame(12, (int) StoreProduct::where('product_id', $p->id)->where('store_id', $entrepot->id)->value('quantity'));

        // Journal : sortie et entrée en pièces.
        $this->assertSame(['pcs'], StockMovement::whereIn('type', ['TRANSFER_OUT', 'TRANSFER_IN'])->pluck('unite')->unique()->values()->all());

        $this->actingAs($this->admin)->get(route('transfers.index'))->assertSee('12 pièces');
        $this->actingAs($this->admin)->get(route('receipts.transfers.show', $transfert))->assertOk()->assertSee('12 pièces');
        $this->actingAs($this->admin)->get(route('stock.show', $this->magasin))->assertOk()->assertSee('−12 pièces');
        $this->actingAs($this->admin)->get(route('stock.movements', ['store_id' => $entrepot->id]))->assertOk()->assertSee('+12 pièces');
        $this->actingAs($this->admin)->get(route('transfers.edit', $transfert->id))->assertOk()->assertSee('Quantité (en pièces)');
    }

    public function test_resume_des_quantites_en_pieces(): void
    {
        $this->assertSame('43 pièces', Product::resumeQuantites([
            ['unite' => 'pcs', 'qty' => 5], ['unite' => 'pcs', 'qty' => 30],
            ['unite' => 'pcs', 'qty' => 7], ['unite' => 'pcs', 'qty' => 1],
        ]));
        $this->assertSame('0', Product::resumeQuantites([]));
    }

    public function test_rapports_et_exports_a_la_piece(): void
    {
        $this->creerProduit([]);
        $carton = Product::firstOrFail();
        $this->creerProduit([]);
        $piece = Product::latest('id')->firstOrFail();

        $this->vendre($carton, 2, 3000)->assertSessionHas('success');
        $this->vendre($piece, 5, 200)->assertSessionHas('success');

        // Ventes flash : total, par vendeur et par emplacement, ligne par ligne.
        $flash = $this->actingAs($this->admin)->get(route('reports.flash', ['period' => 'daily']))->assertOk();
        $this->assertSame('7 pièces', $flash->viewData('totals')['qty_unites']);
        $this->assertSame('7 pièces', $flash->viewData('qtyVendeur')[$this->admin->id]);
        $this->assertSame('7 pièces', $flash->viewData('qtyStore')[$this->magasin->id]);
        $flash->assertSee('2 pièces');

        // Rapport des ventes et exports.
        $this->actingAs($this->admin)->get(route('reports.sales', ['period' => 'daily']))->assertOk()->assertSee('7 pièces');
        $this->actingAs($this->admin)->get(route('sales.export-pdf', ['period' => 'daily']))->assertOk();
        $excel = view('exports.sales', ['sales' => Sale::all(), 'totalAmount' => 0, 'totalInterest' => 0, 'totalQuantity' => 0])->render();
        $this->assertStringContainsString('Unité', $excel);
        $this->assertStringContainsString('pièces', $excel);
        $this->assertStringContainsString('7 pièces', $excel);

        // Tableau de bord : stock détaillé par unité.
        $this->assertSame('993 pièces', Product::resumeStock());
        // (La page du tableau de bord utilise MONTH(), propre à MySQL : vérifiée sur la base réelle.)
    }

    public function test_recus_et_bons_a_la_piece(): void
    {
        $this->creerProduit([]);
        $carton = Product::firstOrFail();
        $this->creerProduit([]);
        $piece = Product::latest('id')->firstOrFail();

        $this->actingAs($this->admin)->post(route('sales.store'), [
            'numeroFacture' => 'F-BON', 'store_id' => $this->magasin->id, 'customer_id' => $this->client->id,
            'avance' => 1000, 'paid_by' => 'cash',
            'sales' => [
                1 => ['product_id' => $carton->id, 'quantity' => 2, 'prix' => 3000, 'store_id' => $this->magasin->id],
                2 => ['product_id' => $piece->id, 'quantity' => 5, 'prix' => 200, 'store_id' => $this->magasin->id],
            ],
        ])->assertSessionHas('success');

        // Bon de sortie : conditionnement et total par unité.
        $this->actingAs($this->admin)->get(route('factures.bon-de-sortie', 'F-BON'))->assertOk()
            ->assertSee('2 pièces')->assertSee('Conditionnement : Pièce')->assertSee('7 pièces');

        // Bon de commande : résumé des articles.
        $this->actingAs($this->admin)->get(route('factures.bon-de-commande', 'F-BON'))->assertOk()
            ->assertSee('7 pièces');

        // Reçu de paiement : articles de la facture avec leur unité.
        $paiement = Payment::whereHas('facture', fn ($q) => $q->where('numero_facture', 'F-BON'))->firstOrFail();
        $this->actingAs($this->admin)->get(route('receipts.payments.show', $paiement))->assertOk()
            ->assertSee('2 pièces')->assertSee('3 000 FG / pièce')->assertSee('5 pièces');
    }

    public function test_inventaire_et_ajustement_a_la_piece(): void
    {
        $this->creerProduit([]);
        $carton = Product::firstOrFail();
        $this->creerProduit([]);

        // Formulaire : unité du produit et stock enregistré pour l'aperçu de l'écart.
        $form = $this->actingAs($this->admin)->get(route('stock.adjust.form'))->assertOk()
            ->assertSee('Pièce')->assertSee('data-pluriel="pièces"', false);
        $this->assertSame(500, $form->viewData('stockEnregistre')[$this->magasin->id.'|'.$carton->id]);

        // Inventaire : 500 pièces enregistrés, 497 comptés.
        $this->actingAs($this->admin)->post(route('stock.adjust'), [
            'store_id' => $this->magasin->id, 'product_id' => $carton->id, 'counted' => 497,
            'type' => 'INVENTORY', 'reason' => 'Inventaire mensuel',
        ])->assertSessionHas('success', fn ($m) => str_contains($m, '500 → 497 pièces (écart −3 pièces)'));

        $mouvement = StockMovement::where('type', 'INVENTORY')->firstOrFail();
        $this->assertSame('pcs', $mouvement->unite);
        $this->assertSame(-3, (int) $mouvement->quantity);

        // Ajustement (casse) : écart positif ou négatif, toujours en pièces.
        $this->actingAs($this->admin)->post(route('stock.adjust'), [
            'store_id' => $this->magasin->id, 'product_id' => $carton->id, 'counted' => 498,
            'type' => 'ADJUSTMENT', 'reason' => 'Carton retrouvé',
        ])->assertSessionHas('success', fn ($m) => str_contains($m, '(écart +1 pièce)'));

        // Stock par emplacement : détail par unité, jamais d'addition mélangée.
        $index = $this->actingAs($this->admin)->get(route('stock.index'))->assertOk();
        $this->assertSame('998 pièces', $index->viewData('totals')['stock_unites']);
        $index->assertSee('998 pièces');
        $this->actingAs($this->admin)->get(route('stock.show', $this->magasin))->assertOk()->assertSee('−3 pièces');
    }

    public function test_messages_et_listes_a_la_piece(): void
    {
        $this->creerProduit([]);
        $carton = Product::firstOrFail();

        // Stock insuffisant : quantités dans l'unité du produit.
        $this->vendre($carton, 501, 3000)->assertSessionHas('error', fn ($m) => str_contains($m, 'Disponible : 500 pièces. Demandé : 501 pièces.'));

        // Liste des factures : quantité par unité.
        $this->vendre($carton, 2, 3000)->assertSessionHas('success');
        $this->actingAs($this->admin)->get(route('factures.index'))->assertOk()->assertSee('2 pièces');

        // Approbation d'une commande en ligne : unité dans la vérification de stock.
        $client = User::create([
            'name' => 'Client web', 'email' => 'web2@example.com', 'username' => 'web2',
            'password' => Hash::make('password'), 'role_id' => User::ROLE_CUSTOMER,
            'phone' => '622', 'status' => 'Active', 'token' => '',
        ]);
        $adresse = DeliveryAddress::create(['user_id' => $client->id, 'full_address' => 'Madina', 'city' => 'Conakry', 'phone' => '622']);
        $order = Order::create(['user_id' => $client->id, 'delivery_address_id' => $adresse->id, 'total_amount' => 9000, 'status' => 'pending']);
        OrderItem::create(['order_id' => $order->id, 'product_id' => $carton->id, 'quantity' => 3, 'price' => 3000]);

        $this->actingAs($this->admin)->getJson(route('admin.orders.stock.check', $order))->assertOk()
            ->assertJsonPath('items.0.unite', 'pièces')->assertJsonPath('items.0.requested', 3);
    }

    public function test_recu_de_transaction_solde_apres_et_factures_reglees(): void
    {
        $this->creerProduit([]);
        $carton = Product::firstOrFail();
        // Deux factures à crédit : 2 pièces (6 000) puis 1 pièce (3 000).
        $this->vendre($carton, 2, 3000)->assertSessionHas('success');
        $this->vendre($carton, 1, 3000)->assertSessionHas('success');
        $this->assertEquals(9000, (float) $this->client->fresh()->balance);

        // Versement global de 7 000 : la 1re facture soldée, 1 000 sur la 2e.
        $this->actingAs($this->admin)->post(route('paiementsClient.store'), [
            'customer_id' => $this->client->id, 'versement' => '7 000', 'paid_by' => 'cash', 'note' => 'Versement',
        ])->assertRedirect();

        $transaction = TransactionFacture::firstOrFail();
        $this->assertEquals(9000, $transaction->solde_avant);
        $this->assertEquals(2000, $transaction->solde_apres);
        $this->assertCount(2, $transaction->payments);

        $recu = $this->actingAs($this->admin)->get(route('receipts.transactions.show', $transaction))->assertOk();
        $recu->assertSee('Solde avant versement')->assertSee('9,000.00')
            ->assertSee('Reste à payer après ce versement')->assertSee('2,000.00')
            ->assertDontSee('Balance restante')
            ->assertSee('2 pièces')->assertSee('1 pièce')->assertSee('Soldée')
            ->assertSee('if (unePage) {', false);

        // Historique du client : reste à payer après chaque versement.
        $this->actingAs($this->admin)->get(route('paiementsClient.add', $this->client->mark))->assertOk()
            ->assertSee('Reste à payer après versement')->assertSee('2 000 FG');

        // Ancien versement (sans solde après enregistré) : déduit du solde avant.
        $ancien = TransactionFacture::create(['customer_id' => $this->client->id, 'versement' => 500, 'balance' => 2000, 'receipt_number' => 'RCC-ANCIEN']);
        $this->assertEquals(1500, $ancien->solde_apres);
        $this->actingAs($this->admin)->get(route('receipts.transactions.show', $ancien))->assertOk()->assertSee('1,500.00');
    }

    public function test_bon_de_transfert_statut_circuit_et_perimetre(): void
    {
        $this->creerProduit([]);
        $p = Product::firstOrFail();
        $entrepot = Store::create(['store_name' => 'Entrepôt Madina', 'place_id' => $this->magasin->place_id, 'user_id' => $this->admin->id,
            'store_picture' => '', 'description' => 'x', 'status' => 1, 'type' => 'entrepot', 'parent_id' => $this->magasin->id]);
        $autre = Store::create(['store_name' => 'Boutique Kaloum', 'place_id' => $this->magasin->place_id, 'user_id' => $this->admin->id,
            'store_picture' => '', 'description' => 'x', 'status' => 1, 'type' => 'magasin']);

        // Transfert demandé, pas encore autorisé : le bon ne dit pas « confirmé ».
        $this->actingAs($this->admin)->post(route('transfers.store'), [
            'product_id' => $p->id, 'from_store_id' => $this->magasin->id, 'to_store_id' => $entrepot->id, 'quantity' => 5,
        ])->assertSessionHas('success');
        $enAttente = StockTransfer::firstOrFail();
        $this->actingAs($this->admin)->get(route('receipts.transfers.show', $enAttente))->assertOk()
            ->assertSee('BON DE TRANSFERT')->assertSee($enAttente->reference)
            ->assertSee("En attente d'autorisation")->assertDontSee('Transfert confirmé')
            ->assertSee('5 pièces')->assertSee('Pièce')
            ->assertSee("Ce transfert n'est pas terminé", false)
            ->assertSee('Reçu par (arrivée)');

        // Transfert exécuté : circuit complet, réceptionné.
        $this->actingAs($this->admin)->post(route('transfers.store'), [
            'product_id' => $p->id, 'from_store_id' => $this->magasin->id, 'to_store_id' => $entrepot->id, 'quantity' => 3, 'execute_now' => 1,
        ])->assertSessionHas('success');
        $termine = StockTransfer::latest('id')->firstOrFail();
        $this->assertSame('completed', $termine->status);
        $bon = $this->actingAs($this->admin)->get(route('receipts.transfers.show', $termine))->assertOk();
        $bon->assertSee('Réceptionné')->assertDontSee("Ce transfert n'est pas terminé", false)->assertSee('Patron');

        // Vendeur d'un autre magasin : accès refusé.
        $vendeur = User::create([
            'name' => 'Vendeur Kaloum', 'email' => 'kaloum@example.com', 'username' => 'kaloum',
            'password' => Hash::make('password'), 'role_id' => User::ROLE_STORE_MANAGER,
            'phone' => '623', 'status' => 'Active', 'token' => '',
        ]);
        $vendeur->assignToStore($autre->id);
        $this->actingAs($vendeur)->get(route('receipts.transfers.show', $termine))->assertForbidden();
    }
}
