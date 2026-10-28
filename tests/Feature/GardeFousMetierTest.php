<?php

namespace Tests\Feature;

use App\Models\InventorySession;
use App\Models\Place;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Store;
use App\Models\User;
use App\Services\InventaireService;
use App\Services\StockService;
use App\Support\ModeDeVente;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

/**
 * Trois pièges relevés en éprouvant le projet aux limites. Aucun ne produisait
 * d'erreur : tous rendaient un résultat faux, ou fermaient une porte sans la
 * rouvrir.
 */
class GardeFousMetierTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Store $boutique;

    private Product $ciment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->admin = User::create([
            'name' => 'Patron', 'email' => 'patron@example.com', 'username' => 'patron',
            'password' => Hash::make('password'), 'role_id' => User::ROLE_ADMIN,
            'phone' => '620', 'status' => 'Active', 'token' => '',
        ])->fresh();

        $lieu = Place::create(['placeName' => 'Conakry', 'countryName' => 'Guinée', 'description' => 'x']);
        $this->boutique = Store::create([
            'store_name' => 'Boutique', 'place_id' => $lieu->id, 'user_id' => $this->admin->id,
            'store_picture' => '', 'description' => 'x', 'status' => 1, 'type' => Store::TYPE_MAGASIN,
        ]);
        $this->ciment = Product::create([
            'libelle' => 'Ciment 50kg', 'sku' => 'CIM', 'description' => 'x',
            'price_sale' => 45000, 'price' => 60000,
        ]);
    }

    // -----------------------------------------------------------------
    // 1. Un comptage daté du futur bloquait la boutique pour de bon
    // -----------------------------------------------------------------

    /**
     * Les deux garde-fous existants se retournaient contre l'utilisateur : le
     * comptage suivant était refusé comme antérieur, et un comptage validé ne
     * se supprime pas. Une faute de frappe sur l'année — 2029 pour 2026 —
     * fermait la boutique jusqu'à cette date, sans aucun recours par l'écran.
     */
    public function test_un_comptage_ne_peut_pas_etre_date_du_futur(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('on ne compte pas un stock à venir');

        app(InventaireService::class)->preparer(
            $this->boutique->id,
            Carbon::tomorrow()->toDateString(),
            $this->admin->id
        );
    }

    public function test_le_formulaire_refuse_aussi_une_date_future(): void
    {
        config(['sales.mode' => ModeDeVente::INVENTAIRE]);

        $this->actingAs($this->admin)->post(route('inventaires.store'), [
            'store_id' => $this->boutique->id,
            'counted_on' => Carbon::today()->addYears(3)->toDateString(),
        ])->assertSessionHasErrors('counted_on');

        $this->assertSame(0, InventorySession::count());
    }

    public function test_un_comptage_du_jour_reste_possible(): void
    {
        config(['sales.mode' => ModeDeVente::INVENTAIRE]);

        $seance = app(InventaireService::class)->preparer(
            $this->boutique->id, Carbon::today()->toDateString(), $this->admin->id
        );

        $this->assertSame(Carbon::today()->toDateString(), $seance->counted_on->toDateString());
    }

    // -----------------------------------------------------------------
    // 2. Un produit en stock partait sans un mot
    // -----------------------------------------------------------------

    /**
     * La base protège un produit vendu — « sales.product_id » porte une
     * contrainte. Mais une installation qui déduit son chiffre d'affaires des
     * comptages n'enregistre aucune vente : il ne reste que des mouvements de
     * stock, et cette colonne-là n'a pas de contrainte. Le produit partait, son
     * stock avec lui, et le mouvement restait orphelin dans le journal.
     */
    public function test_un_produit_encore_en_stock_ne_se_supprime_pas(): void
    {
        app(StockService::class)->increment($this->boutique->id, $this->ciment->id, 40, [
            'type' => StockMovement::PURCHASE,
        ]);

        try {
            $this->ciment->delete();
            $this->fail('Le produit a été supprimé alors qu\'il restait 40 sacs en stock.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('en stock', $e->getMessage());
        }

        $this->assertNotNull(Product::find($this->ciment->id));
        $this->assertSame(40, app(StockService::class)->availableInStore($this->boutique->id, $this->ciment->id));
    }

    public function test_un_produit_cite_par_un_mouvement_ne_se_supprime_pas(): void
    {
        $service = app(StockService::class);
        $service->increment($this->boutique->id, $this->ciment->id, 10, ['type' => StockMovement::PURCHASE]);
        $service->forceDecrement($this->boutique->id, $this->ciment->id, 10, ['type' => StockMovement::SALE]);

        $this->assertSame(0, $service->availableInStore($this->boutique->id, $this->ciment->id));

        try {
            $this->ciment->delete();
            $this->fail('Le produit a été supprimé alors que son histoire reste dans le journal.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('mouvements de stock', $e->getMessage());
        }

        $this->assertNotNull(Product::find($this->ciment->id));
    }

    /** Un produit sans histoire se supprime toujours : le verrou ne bloque rien d'utile. */
    public function test_un_produit_sans_histoire_se_supprime_toujours(): void
    {
        $neuf = Product::create([
            'libelle' => 'Saisi par erreur', 'sku' => 'ERR', 'description' => 'x',
            'price_sale' => 1000, 'price' => 1300,
        ]);

        $neuf->delete();

        $this->assertNull(Product::find($neuf->id));
    }

    public function test_l_ecran_affiche_la_raison_du_refus(): void
    {
        app(StockService::class)->increment($this->boutique->id, $this->ciment->id, 5, [
            'type' => StockMovement::PURCHASE,
        ]);

        $this->actingAs($this->admin)
            ->delete(route('produits.destroy', $this->ciment->id))
            ->assertSessionHas('error', fn ($message) => str_contains($message, 'en stock'));
    }

    // -----------------------------------------------------------------
    // 3. Le rapport mensuel affichait le mauvais mois
    // -----------------------------------------------------------------

    /**
     * « Y-m » ne porte pas de jour : Carbon le prenait sur la date du jour. Un
     * 31, demander février renvoyait le 3 mars, et le rapport affichait mars
     * sans rien signaler. Le défaut ne se voyait que trois ou quatre jours par
     * mois — et le rapport mensuel est le seul chiffre d'affaires de ce client.
     */
    public function test_le_rapport_mensuel_vise_le_bon_mois_meme_un_31(): void
    {
        config(['sales.mode' => ModeDeVente::INVENTAIRE]);
        Carbon::setTestNow('2027-12-31 10:00:00');

        foreach (['2027-02' => ['2027-02-01', '2027-02-28'], '2027-06' => ['2027-06-01', '2027-06-30']] as $mois => $bornes) {
            $reponse = $this->actingAs($this->admin)
                ->get(route('reports.sales', ['period' => 'monthly', 'month' => $mois]))
                ->assertOk();

            $this->assertSame($bornes[0], $reponse->viewData('start')->toDateString(), "Début de $mois");
            $this->assertSame($bornes[1], $reponse->viewData('end')->toDateString(), "Fin de $mois");
        }

        Carbon::setTestNow();
    }
}
