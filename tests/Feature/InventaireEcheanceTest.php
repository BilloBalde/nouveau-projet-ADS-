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
 * L'inventaire se fait tous les six mois. Deux semaines avant l'échéance,
 * une alerte le rappelle ; une fois l'échéance passée, elle passe au rouge.
 * Une boutique jamais comptée est signalée aussi : sans premier comptage,
 * aucun chiffre d'affaires ne peut être déduit.
 */
class InventaireEcheanceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Product $ciment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-11-02 09:00:00');
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

    /** Boutique avec du stock, et un comptage validé à la date donnée. */
    private function boutique(string $nom, ?string $dernierComptage = null): Store
    {
        $store = Store::create(['store_name' => $nom, 'place_id' => Place::firstOrFail()->id, 'user_id' => $this->admin->id,
            'store_picture' => '', 'description' => 'x', 'status' => 1, 'type' => 'magasin']);
        StoreProduct::create(['store_id' => $store->id, 'product_id' => $this->ciment->id, 'quantity' => 10]);

        if ($dernierComptage) {
            InventorySession::create(['reference' => 'INV-'.$nom, 'store_id' => $store->id, 'counted_on' => $dernierComptage,
                'status' => InventorySession::VALIDEE, 'validated_at' => $dernierComptage]);
        }

        return $store;
    }

    private function etats(): array
    {
        return app(InventaireRapportService::class)->echeances()
            ->mapWithKeys(fn ($e) => [$e->store->store_name => [$e->etat, $e->jours]])->all();
    }

    public function test_l_echeance_est_six_mois_apres_le_dernier_comptage(): void
    {
        $this->boutique('Madina', '2026-05-12');   // échéance 12/11 : dans 10 jours
        $this->boutique('Kaloum', '2026-04-20');   // échéance 20/10 : 13 jours de retard
        $this->boutique('Matoto', '2026-09-01');   // échéance 01/03/2027 : à jour
        $this->boutique('Ratoma');                 // jamais comptée

        $this->assertSame([
            'Kaloum' => ['en_retard', -13],
            'Ratoma' => ['a_faire', null],
            'Madina' => ['bientot', 10],
            'Matoto' => ['a_jour', 119],
        ], $this->etats());
    }

    public function test_l_alerte_commence_deux_semaines_avant(): void
    {
        $this->boutique('Madina', '2026-05-17'); // échéance le 17/11 : dans 15 jours
        $this->assertSame('a_jour', $this->etats()['Madina'][0]);

        $this->travelTo('2026-11-03 09:00:00'); // dans 14 jours
        $this->assertSame('bientot', $this->etats()['Madina'][0]);
    }

    public function test_seul_le_dernier_comptage_valide_compte(): void
    {
        $madina = $this->boutique('Madina', '2026-01-10');
        // Un comptage plus récent, mais pas encore validé, ne repousse rien.
        InventorySession::create(['reference' => 'INV-BROUILLON', 'store_id' => $madina->id, 'counted_on' => '2026-10-30',
            'status' => InventorySession::BROUILLON]);

        $this->assertSame('en_retard', $this->etats()['Madina'][0]);
    }

    public function test_un_emplacement_vide_et_jamais_compte_n_est_pas_signale(): void
    {
        Store::create(['store_name' => 'Vide', 'place_id' => Place::firstOrFail()->id, 'user_id' => $this->admin->id,
            'store_picture' => '', 'description' => 'x', 'status' => 1, 'type' => 'entrepot']);

        $this->assertArrayNotHasKey('Vide', $this->etats());
    }

    public function test_le_rythme_se_regle(): void
    {
        config(['sales.inventaire_tous_les_mois' => 3, 'sales.inventaire_alerte_jours' => 7]);
        $this->boutique('Madina', '2026-08-06'); // échéance 06/11 : dans 4 jours

        $this->assertSame(['bientot', 4], $this->etats()['Madina']);
    }

    public function test_le_tableau_de_bord_et_l_en_tete_le_rappellent(): void
    {
        $this->boutique('Madina', '2026-05-12');
        $this->boutique('Kaloum', '2026-04-20');

        $this->actingAs($this->admin)->get(route('home'))->assertOk()
            ->assertSee('Inventaire en retard à Kaloum')
            ->assertSee('Inventaire à faire à Madina avant le 12/11/2026')
            ->assertSee('Faire le comptage');

        $this->actingAs($this->admin)->get(route('inventaires.index'))->assertOk()
            ->assertSee('Inventaire en retard à Kaloum');

        // L'en-tête porte le rappel sur toutes les pages.
        $this->actingAs($this->admin)->get(route('associates.index'))->assertOk()->assertSee('Inventaire en retard');
    }

    public function test_pas_de_rappel_en_mode_caisse(): void
    {
        $this->boutique('Kaloum', '2026-04-20');
        config(['sales.mode' => ModeDeVente::CAISSE]);

        $this->actingAs($this->admin)->get(route('associates.index'))->assertOk()->assertDontSee('Inventaire en retard');
    }
}
