<?php

namespace Tests\Feature;

use App\Models\InventorySession;
use App\Models\Place;
use App\Models\Product;
use App\Models\Role;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\User;
use App\Services\InventaireRapportService;
use App\Services\InventaireService;
use App\Support\ModeDeVente;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Corriger ou supprimer un comptage validé : réservé à l'administrateur et
 * au superuser, et au dernier comptage de la boutique. Dans les deux cas le
 * stock revient exactement à ce qu'il était avant le comptage.
 */
class InventaireCorrectionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $superuser;

    private User $responsable;

    private Store $madina;

    private Product $ciment;

    private InventorySession $premier;

    private InventorySession $second;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo('2026-11-03 09:00:00');
        $this->seed(RolesSeeder::class);
        $this->seed(PermissionSeeder::class);
        config(['sales.mode' => ModeDeVente::INVENTAIRE]);

        $this->admin = $this->user('Patron', 'admin');
        Place::create(['placeName' => 'Conakry', 'countryName' => 'Guinée', 'description' => 'test']);
        $this->madina = Store::create(['store_name' => 'Madina', 'place_id' => Place::firstOrFail()->id, 'user_id' => $this->admin->id,
            'store_picture' => '', 'description' => 'x', 'status' => 1, 'type' => 'magasin', 'is_main' => true]);
        $this->superuser = $this->user('Manager', 'superuser');
        $this->responsable = $this->user('Awa', 'responsable', $this->madina->id);

        // Vente 50 000, revient 38 000.
        $this->ciment = Product::create(['libelle' => 'Ciment', 'sku' => 'CIM', 'description' => 'x',
            'price' => 50_000, 'price_sale' => 38_000, 'price_manuel' => true]);
        StoreProduct::create(['store_id' => $this->madina->id, 'product_id' => $this->ciment->id, 'quantity' => 100]);

        // Premier comptage : 100 sacs. Second : 70 → 30 vendus, 1 500 000 de CA.
        $service = app(InventaireService::class);
        $this->premier = $service->valider($service->preparer($this->madina->id, '2026-10-01'), [
            ['product_id' => $this->ciment->id, 'counted' => 100],
        ]);
        $this->second = $service->valider($service->preparer($this->madina->id, '2026-10-31'), [
            ['product_id' => $this->ciment->id, 'counted' => 70],
        ]);
    }

    private function user(string $name, string $role, ?int $storeId = null): User
    {
        $user = User::create([
            'name' => $name, 'email' => str()->slug($name).'@example.com', 'username' => str()->slug($name),
            'password' => Hash::make('password'), 'role_id' => (int) Role::where('slug', $role)->value('id'),
            'phone' => '620', 'status' => 'Active', 'token' => '',
        ]);
        if ($storeId) {
            $user->assignToStore($storeId);
        }

        return $user->fresh();
    }

    private function stock(): int
    {
        return (int) StoreProduct::where('store_id', $this->madina->id)->where('product_id', $this->ciment->id)->sum('quantity');
    }

    private function caDeLAnnee(): float
    {
        return app(InventaireRapportService::class)->tableauDeBord(null, 2026)['ca_annee'];
    }

    public function test_le_point_de_depart(): void
    {
        $this->assertSame(70, $this->stock());
        $this->assertEquals(1_500_000, $this->caDeLAnnee());
    }

    public function test_l_admin_modifie_un_comptage_valide(): void
    {
        $this->actingAs($this->admin)->post(route('inventaires.rouvrir', $this->second->id))
            ->assertRedirect(route('inventaires.edit', $this->second->id));

        // Rouvert : le stock revient à avant le comptage, son CA sort des rapports.
        $this->second->refresh();
        $this->assertSame(InventorySession::BROUILLON, $this->second->status);
        $this->assertSame(100, $this->stock());
        $this->assertEquals(0, $this->caDeLAnnee());

        // La saisie est préremplie avec les quantités comptées.
        $this->actingAs($this->admin)->get(route('inventaires.edit', $this->second->id))->assertOk()
            ->assertSee('name="lignes[0][counted]" data-compte', false)
            ->assertSee('value="70"', false);

        // Corrigé : 80 sacs comptés, donc 20 vendus.
        $this->actingAs($this->admin)->post(route('inventaires.valider', $this->second->id), [
            'lignes' => [['product_id' => $this->ciment->id, 'counted' => 80]],
        ])->assertRedirect();

        $this->assertSame(InventorySession::VALIDEE, $this->second->fresh()->status);
        $this->assertSame(80, $this->stock());
        $this->assertEquals(1_000_000, $this->caDeLAnnee());
    }

    public function test_le_superuser_supprime_un_comptage_valide(): void
    {
        $this->actingAs($this->superuser)->delete(route('inventaires.destroy', $this->second->id))
            ->assertRedirect(route('inventaires.index'))
            ->assertSessionHas('success');

        $this->assertNull(InventorySession::find($this->second->id));
        $this->assertSame(100, $this->stock());
        $this->assertEquals(0, $this->caDeLAnnee());

        // Le comptage suivant repart du premier, comme si l'autre n'avait jamais existé.
        $service = app(InventaireService::class);
        $nouveau = $service->valider($service->preparer($this->madina->id, '2026-11-02'), [
            ['product_id' => $this->ciment->id, 'counted' => 60],
        ]);
        $this->assertSame($this->premier->id, (int) $nouveau->previous_session_id);
        $this->assertSame(40, (int) $nouveau->total_sold_quantity);
    }

    public function test_rouvert_revalide_puis_supprime_le_stock_reste_juste(): void
    {
        $service = app(InventaireService::class);
        $service->rouvrir($this->second);
        $service->valider($this->second->fresh(), [['product_id' => $this->ciment->id, 'counted' => 90]]);
        $this->assertSame(90, $this->stock());

        $service->supprimer($this->second->fresh());
        $this->assertSame(100, $this->stock());
    }

    public function test_seul_le_dernier_comptage_se_corrige(): void
    {
        $this->actingAs($this->admin)->post(route('inventaires.rouvrir', $this->premier->id))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Seul le dernier comptage'));
        $this->actingAs($this->admin)->delete(route('inventaires.destroy', $this->premier->id))
            ->assertSessionHas('error');

        $this->assertSame(InventorySession::VALIDEE, $this->premier->fresh()->status);
        $this->assertSame(70, $this->stock());
    }

    public function test_un_autre_role_ne_peut_ni_modifier_ni_supprimer_un_comptage_valide(): void
    {
        $this->actingAs($this->responsable)->post(route('inventaires.rouvrir', $this->second->id))->assertForbidden();
        $this->actingAs($this->responsable)->delete(route('inventaires.destroy', $this->second->id))->assertForbidden();

        $this->assertSame(InventorySession::VALIDEE, $this->second->fresh()->status);
        $this->assertSame(70, $this->stock());

        // Un comptage encore en saisie, lui, se jette comme avant.
        $brouillon = app(InventaireService::class)->preparer($this->madina->id, '2026-11-03');
        $this->actingAs($this->responsable)->delete(route('inventaires.destroy', $brouillon->id))->assertRedirect(route('inventaires.index'));
        $this->assertNull(InventorySession::find($brouillon->id));
    }

    public function test_les_boutons_n_apparaissent_que_pour_l_admin_et_le_superuser(): void
    {
        $this->actingAs($this->admin)->get(route('inventaires.show', $this->second->id))->assertOk()
            ->assertSee(route('inventaires.rouvrir', $this->second->id))
            ->assertSee('Supprimer');
        $this->actingAs($this->superuser)->get(route('inventaires.show', $this->second->id))->assertOk()
            ->assertSee(route('inventaires.rouvrir', $this->second->id));

        $this->actingAs($this->responsable)->get(route('inventaires.show', $this->second->id))->assertOk()
            ->assertDontSee(route('inventaires.rouvrir', $this->second->id));

        // Le premier comptage a une suite : pas de bouton, une explication.
        $this->actingAs($this->admin)->get(route('inventaires.show', $this->premier->id))->assertOk()
            ->assertDontSee(route('inventaires.rouvrir', $this->premier->id))
            ->assertSee('Un comptage plus récent part de celui-ci');
    }
}
