<?php

namespace Tests\Feature;

use App\Models\Associate;
use App\Models\AssociateProfitShare;
use App\Models\AssociateYearClosing;
use App\Models\Expense;
use App\Models\Place;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Store;
use App\Models\User;
use App\Services\AssociesService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

/**
 * Associés : une part du bénéfice saisie à la main, des sorties en cours
 * d'année, et une clôture qui solde chacun.
 *
 *   report + part − sorties = solde ; positif → versé, négatif → reporté.
 *
 * Les montants attendus sont écrits en clair pour qu'une dérive du calcul se
 * voie sur le chiffre.
 */
class AssociesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $vendeur;

    private Store $madina;

    private Associate $diallo;

    private Associate $bah;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->travelTo(Carbon::create(2026, 6, 15, 10));

        $this->admin = $this->user('Patron', User::ROLE_ADMIN);
        $lieu = Place::create(['placeName' => 'Conakry', 'countryName' => 'Guinée', 'description' => 'test']);
        $this->madina = Store::create(['store_name' => 'Madina', 'place_id' => $lieu->id, 'user_id' => $this->admin->id,
            'store_picture' => '', 'description' => 'x', 'status' => 1, 'type' => 'magasin', 'is_main' => true]);
        $this->vendeur = $this->user('Awa', User::ROLE_STORE_MANAGER, $this->madina->id);

        $this->diallo = Associate::create(['name' => 'Mamadou Diallo', 'phone' => '620']);
        $this->bah = Associate::create(['name' => 'Ibrahima Bah']);
    }

    private function user(string $name, int $role, ?int $storeId = null): User
    {
        $user = User::create([
            'name' => $name, 'email' => str()->slug($name).uniqid().'@example.com', 'username' => str()->slug($name).uniqid(),
            'password' => Hash::make('password'), 'role_id' => $role, 'phone' => '620', 'status' => 'Active', 'token' => '',
        ]);
        if ($storeId) {
            $user->assignToStore($storeId);
        }

        return $user->fresh();
    }

    private function sortie(Associate $associe, float $montant, string $motif = 'Avance'): Expense
    {
        return Expense::create(['reference' => 'DEP-'.uniqid(), 'associate_id' => $associe->id, 'amount' => $montant,
            'description' => $motif, 'store_id' => $this->madina->id, 'user_id' => $this->admin->id, 'status' => 'Completed']);
    }

    private function service(): AssociesService
    {
        return app(AssociesService::class);
    }

    // -----------------------------------------------------------------
    // Sorties
    // -----------------------------------------------------------------

    public function test_une_sortie_exige_l_associe_le_montant_et_le_motif(): void
    {
        $this->actingAs($this->admin)->post(route('expenses.store'), [
            'reference' => 'DEP-1', 'store_id' => $this->madina->id,
        ])->assertSessionHasErrors(['associate_id', 'amount', 'description']);

        $this->actingAs($this->admin)->post(route('expenses.store'), [
            'reference' => 'DEP-1', 'store_id' => $this->madina->id, 'associate_id' => $this->diallo->id,
            'amount' => 2_000_000, 'description' => 'Avance sur sa part',
        ])->assertRedirect(route('expenses.index'))->assertSessionHas('success');

        $sortie = Expense::firstOrFail();
        $this->assertSame($this->diallo->id, (int) $sortie->associate_id);
        // Deux millions : la colonne en decimal(8,2) n'en acceptait pas un.
        $this->assertEquals(2_000_000, (float) $sortie->amount);
        $this->assertSame($this->admin->id, (int) $sortie->user_id);

        $this->actingAs($this->admin)->get(route('expenses.index'))->assertOk()
            ->assertSee('Mamadou Diallo')->assertSee('Avance sur sa part')->assertSee('2 000 000 FG');
    }

    public function test_le_formulaire_propose_les_associes_actifs(): void
    {
        $this->bah->update(['active' => false]);

        $this->actingAs($this->admin)->get(route('expenses.create'))->assertOk()
            ->assertSee('Mamadou Diallo')->assertDontSee('Ibrahima Bah')->assertDontSee('Category');
    }

    // -----------------------------------------------------------------
    // Compte de l'année
    // -----------------------------------------------------------------

    public function test_le_solde_est_la_part_moins_les_sorties(): void
    {
        $this->sortie($this->diallo, 1_500_000);
        $this->sortie($this->diallo, 500_000);
        $this->service()->enregistrerParts(2026, [$this->diallo->id => 5_000_000]);

        $compte = $this->service()->compte($this->diallo, 2026);

        $this->assertEquals(0, $compte['report']);
        $this->assertEquals(5_000_000, $compte['part']);
        $this->assertEquals(2_000_000, $compte['sorties']);
        $this->assertEquals(3_000_000, $compte['solde']);
        $this->assertEquals(3_000_000, $compte['a_verser']);
        $this->assertEquals(0, $compte['a_reporter']);
        $this->assertNull($compte['cloture']);
    }

    public function test_une_part_nulle_est_retiree_et_une_part_negative_refusee(): void
    {
        $this->service()->enregistrerParts(2026, [$this->diallo->id => 1_000]);
        $this->service()->enregistrerParts(2026, [$this->diallo->id => '']);
        $this->assertSame(0, AssociateProfitShare::count());

        $this->expectException(RuntimeException::class);
        $this->service()->enregistrerParts(2026, [$this->diallo->id => -5]);
    }

    public function test_le_reste_du_benefice_va_a_la_boutique(): void
    {
        config(['sales.mode' => 'caisse']);
        $produit = Product::create(['libelle' => 'Ciment', 'sku' => 'CIM', 'description' => 'x', 'price' => 20_000_000, 'price_sale' => 10_000_000]);
        Sale::forceCreate(['numeroFacture' => 'F-1', 'product_id' => $produit->id, 'store_id' => $this->madina->id, 'user_id' => $this->admin->id,
            'quantity' => 1, 'prix' => 20_000_000, 'prixTotal' => 20_000_000, 'interet' => 10_000_000]);

        $this->actingAs($this->admin)->post(route('associates.parts', 2026), [
            'parts' => [$this->diallo->id => 3_000_000, $this->bah->id => 2_000_000],
        ])->assertRedirect(route('associates.annee', 2026));

        $page = $this->actingAs($this->admin)->get(route('associates.annee', 2026))->assertOk();
        $this->assertEquals(10_000_000, $page->viewData('benefice'));
        $this->assertEquals(5_000_000, $page->viewData('parts'));
        $this->assertEquals(5_000_000, $page->viewData('resteBoutique'));
        $page->assertSee('Reste pour la boutique')->assertSee('5 000 000 FG');
    }

    // -----------------------------------------------------------------
    // Clôture
    // -----------------------------------------------------------------

    public function test_la_cloture_verse_le_solde_positif_et_reporte_le_negatif(): void
    {
        $this->service()->enregistrerParts(2026, [$this->diallo->id => 5_000_000, $this->bah->id => 1_000_000]);
        $this->sortie($this->diallo, 2_000_000);
        $this->sortie($this->bah, 1_600_000);

        $this->actingAs($this->admin)->post(route('associates.cloturer', 2026))
            ->assertRedirect(route('associates.annee', 2026))
            ->assertSessionHas('success', fn ($m) => str_contains($m, '3 000 000 FG'));

        $diallo = AssociateYearClosing::where('associate_id', $this->diallo->id)->where('year', 2026)->firstOrFail();
        $this->assertEquals(3_000_000, (float) $diallo->paid);
        $this->assertEquals(0, (float) $diallo->carried_out);

        // Bah a pris 600 000 de plus que sa part : reporté, rien à lui verser.
        $bah = AssociateYearClosing::where('associate_id', $this->bah->id)->where('year', 2026)->firstOrFail();
        $this->assertEquals(-600_000, (float) $bah->balance);
        $this->assertEquals(0, (float) $bah->paid);
        $this->assertEquals(-600_000, (float) $bah->carried_out);

        // L'année suivante part de ce report et le déduit de sa nouvelle part.
        $this->travelTo(Carbon::create(2027, 3, 1));
        $this->service()->enregistrerParts(2027, [$this->bah->id => 1_000_000]);
        $compte = $this->service()->compte($this->bah, 2027);
        $this->assertEquals(-600_000, $compte['report']);
        $this->assertEquals(400_000, $compte['solde']);
    }

    public function test_une_annee_cloturee_est_figee(): void
    {
        $sortie = $this->sortie($this->diallo, 1_000_000);
        $this->service()->enregistrerParts(2026, [$this->diallo->id => 4_000_000]);
        $this->service()->cloturer(2026);

        // Les parts ne bougent plus.
        $this->actingAs($this->admin)->post(route('associates.parts', 2026), ['parts' => [$this->diallo->id => 9]])
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'clôturée'));
        $this->assertEquals(4_000_000, (float) AssociateProfitShare::value('amount'));

        // Les sorties non plus, quel que soit le chemin.
        $this->actingAs($this->admin)->put(route('expenses.update', $sortie), [
            'reference' => $sortie->reference, 'associate_id' => $this->diallo->id, 'amount' => 1, 'description' => 'x', 'store_id' => $this->madina->id,
        ])->assertSessionHas('error');
        $this->actingAs($this->admin)->delete(route('expenses.destroy', $sortie))->assertSessionHas('error');
        $this->assertEquals(1_000_000, (float) $sortie->fresh()->amount);

        $this->expectException(RuntimeException::class);
        $this->sortie($this->diallo, 10);
    }

    public function test_une_annee_ne_se_cloture_qu_une_fois_et_dans_l_ordre(): void
    {
        $this->sortie($this->diallo, 100_000); // 2026, non clôturée
        $this->travelTo(Carbon::create(2027, 1, 10));

        $this->actingAs($this->admin)->post(route('associates.cloturer', 2027))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'Clôturez d\'abord l\'année 2026'));
        $this->assertSame(0, AssociateYearClosing::count());

        $this->service()->cloturer(2026);
        $this->assertEquals(-100_000, $this->service()->compte($this->diallo, 2027)['report']);

        $this->expectException(RuntimeException::class);
        $this->service()->cloturer(2026);
    }

    public function test_un_associe_desactive_est_quand_meme_solde(): void
    {
        $this->sortie($this->bah, 300_000);
        $this->bah->update(['active' => false]);

        $clotures = $this->service()->cloturer(2026);

        $this->assertContains($this->bah->id, $clotures->pluck('associate_id')->all());
    }

    // -----------------------------------------------------------------
    // Écrans et droits
    // -----------------------------------------------------------------

    public function test_les_ecrans_des_associes(): void
    {
        $this->sortie($this->diallo, 250_000, 'Transport marchandise');

        $this->actingAs($this->admin)->get(route('associates.index'))->assertOk()->assertSee('Mamadou Diallo')->assertSee('-250 000 FG');
        $this->actingAs($this->admin)->get(route('associates.show', $this->diallo))->assertOk()
            ->assertSee('Transport marchandise')->assertSee('Report de l\'année précédente', false);
        $this->actingAs($this->admin)->get(route('associates.annee', 2026))->assertOk()->assertSee('Clôturer l\'année 2026', false);
        $this->actingAs($this->admin)->get(route('associates.create'))->assertOk();

        $this->actingAs($this->admin)->post(route('associates.store'), ['name' => 'Fanta Camara', 'active' => '1'])
            ->assertRedirect();
        $this->assertTrue(Associate::where('name', 'Fanta Camara')->value('active'));
    }

    public function test_un_vendeur_n_atteint_pas_les_associes(): void
    {
        foreach ([route('associates.index'), route('associates.show', $this->diallo), route('associates.annee', 2026)] as $url) {
            $this->actingAs($this->vendeur)->get($url)->assertForbidden();
        }
        $this->actingAs($this->vendeur)->post(route('associates.cloturer', 2026))->assertForbidden();
        $this->assertSame(0, AssociateYearClosing::count());
    }
}
