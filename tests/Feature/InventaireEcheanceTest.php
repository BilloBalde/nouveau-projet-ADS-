<?php

namespace Tests\Feature;

use App\Models\InventorySession;
use App\Models\Place;
use App\Models\Product;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\User;
use App\Services\InventaireRapportService;
use App\Support\ModeDeVente;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * L'inventaire se fait une fois par an, en fin d'année. Du 10 décembre au
 * 10 janvier, un rappel s'affiche pour chaque boutique qui n'a pas encore
 * validé de comptage dans cette période — orange en décembre, rouge en
 * janvier — et disparaît dès que le comptage est validé. Hors de cette
 * période, aucun rappel.
 */
class InventaireEcheanceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Product $ciment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-12-15 09:00:00');
        $this->seed(RolesSeeder::class);
        $this->seed(PermissionSeeder::class);
        config(['sales.mode' => ModeDeVente::INVENTAIRE]);

        $this->admin = User::create([
            'name' => 'Patron', 'email' => 'patron@example.com', 'username' => 'patron',
            'password' => Hash::make('password'), 'role_id' => User::ROLE_ADMIN, 'phone' => '620', 'status' => 'Active', 'token' => '',
        ]);
        Place::create(['placeName' => 'Conakry', 'countryName' => 'Guinée', 'description' => 'test']);
        $this->ciment = Product::create(['libelle' => 'Ciment', 'sku' => 'CIM', 'description' => 'x', 'price' => 50_000, 'price_sale' => 38_000]);
    }

    /** Boutique avec du stock, et éventuellement un comptage validé à cette date. */
    private function boutique(string $nom, ?string $comptage = null): Store
    {
        $store = Store::create(['store_name' => $nom, 'place_id' => Place::firstOrFail()->id, 'user_id' => $this->admin->id,
            'store_picture' => '', 'description' => 'x', 'status' => 1, 'type' => 'magasin']);
        StoreProduct::create(['store_id' => $store->id, 'product_id' => $this->ciment->id, 'quantity' => 10]);

        if ($comptage) {
            $this->comptage($store, $comptage);
        }

        return $store;
    }

    private function comptage(Store $store, string $date, string $statut = InventorySession::VALIDEE): void
    {
        InventorySession::create(['reference' => 'INV-'.$store->id.'-'.$date.'-'.$statut, 'store_id' => $store->id,
            'counted_on' => $date, 'status' => $statut, 'validated_at' => $statut === InventorySession::VALIDEE ? $date : null]);
    }

    private function etats(): array
    {
        return app(InventaireRapportService::class)->echeances()
            ->mapWithKeys(fn ($e) => [$e->store->store_name => [$e->etat, $e->jours]])->all();
    }

    public function test_en_decembre_chaque_boutique_non_comptee_est_rappelee(): void
    {
        $this->boutique('Madina');                  // rien cette année
        $this->boutique('Kaloum', '2025-12-28');    // l'an dernier : ne compte pas pour 2026
        $this->boutique('Matoto', '2026-12-12');    // fait dans la période

        $this->assertSame([
            'Kaloum' => ['bientot', 16],
            'Madina' => ['bientot', 16],
            'Matoto' => ['a_jour', 16],
        ], $this->etats());
    }

    public function test_le_rappel_commence_le_10_decembre(): void
    {
        $this->boutique('Madina');

        $this->travelTo('2026-12-09 18:00:00');
        $this->assertSame([], $this->etats());

        $this->travelTo('2026-12-10 08:00:00');
        $this->assertSame(['bientot', 21], $this->etats()['Madina']);
    }

    public function test_en_janvier_il_passe_au_rouge_jusqu_au_10(): void
    {
        $this->boutique('Madina');

        $this->travelTo('2027-01-04 09:00:00');
        $this->assertSame(['en_retard', -4], $this->etats()['Madina']);

        $this->travelTo('2027-01-10 20:00:00');
        $this->assertSame('en_retard', $this->etats()['Madina'][0]);

        $this->travelTo('2027-01-11 08:00:00');
        $this->assertSame([], $this->etats(), 'Après le 10 janvier, plus de rappel.');
    }

    public function test_il_disparait_des_que_le_comptage_est_valide(): void
    {
        $madina = $this->boutique('Madina');
        $this->assertCount(1, app(InventaireRapportService::class)->alertes());

        // Un comptage en cours ne suffit pas : seul un comptage validé compte.
        $this->comptage($madina, '2026-12-14', InventorySession::BROUILLON);
        $this->assertCount(1, app(InventaireRapportService::class)->alertes());

        $this->comptage($madina, '2026-12-15');
        $this->assertCount(0, app(InventaireRapportService::class)->alertes());

        // Fait en janvier, avant le 10 : la boutique est quitte aussi.
        $kaloum = $this->boutique('Kaloum');
        $this->travelTo('2027-01-05 09:00:00');
        $this->comptage($kaloum, '2027-01-05');
        $this->assertCount(0, app(InventaireRapportService::class)->alertes());
    }

    public function test_hors_periode_aucun_rappel_meme_jamais_comptee(): void
    {
        $this->boutique('Madina');
        $this->travelTo('2026-06-15 09:00:00');

        $this->assertSame([], $this->etats());
    }

    public function test_un_emplacement_vide_n_est_pas_signale(): void
    {
        Store::create(['store_name' => 'Vide', 'place_id' => Place::firstOrFail()->id, 'user_id' => $this->admin->id,
            'store_picture' => '', 'description' => 'x', 'status' => 1, 'type' => 'entrepot']);

        $this->assertArrayNotHasKey('Vide', $this->etats());
    }

    public function test_les_dates_se_reglent(): void
    {
        config(['sales.inventaire_rappel_debut' => '12-01', 'sales.inventaire_rappel_fin' => '01-31']);
        $this->boutique('Madina');

        $this->travelTo('2026-12-01 09:00:00');
        $this->assertSame('bientot', $this->etats()['Madina'][0]);
        $this->travelTo('2027-01-31 09:00:00');
        $this->assertSame('en_retard', $this->etats()['Madina'][0]);
    }

    public function test_le_tableau_de_bord_et_l_en_tete_le_rappellent(): void
    {
        $this->boutique('Madina');

        $this->actingAs($this->admin)->get(route('home'))->assertOk()
            ->assertSee('Inventaire de fin d&#039;année 2026 à faire à Madina avant le 31/12/2026', false)
            ->assertSee('Faire le comptage');
        $this->actingAs($this->admin)->get(route('inventaires.index'))->assertOk()->assertSee('Madina avant le 31/12/2026');
        $this->actingAs($this->admin)->get(route('associates.index'))->assertOk()->assertSee('Inventaire à faire');

        $this->travelTo('2027-01-03 09:00:00');
        $this->actingAs($this->admin)->get(route('home'))->assertOk()
            ->assertSee('pas encore fait à Madina')->assertSee('avant le 10/01/2027');
        $this->actingAs($this->admin)->get(route('associates.index'))->assertOk()->assertSee('Inventaire en retard');
    }

    public function test_pas_de_rappel_en_mode_caisse(): void
    {
        $this->boutique('Madina');
        config(['sales.mode' => ModeDeVente::CAISSE]);

        $this->actingAs($this->admin)->get(route('associates.index'))->assertOk()->assertDontSee('Inventaire à faire');
    }
}
