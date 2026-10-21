<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Devi;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Place;
use App\Models\Product;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Clients, factures, devis et dépenses : quatre modules dont les écrans
 * n'exigeaient que d'être connecté. N'importe quel employé pouvait y créer,
 * modifier et supprimer — y compris supprimer définitivement un client avec
 * ses factures, ou valider un devis, ce qui émet la facture et sort le stock.
 *
 * Ce test vérifie les DEUX sens : le rôle qui doit garder l'accès l'a toujours,
 * et le rôle qui n'a pas la permission est bien refusé. Le second sens compte
 * autant : un simple assertForbidden() ailleurs dans la suite passerait aussi
 * si l'accès était refusé pour la mauvaise raison.
 */
class PermissionsModulesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $vendeur;
    private User $magasinier;
    private User $comptable;
    private Store $madina;
    private Customer $client;
    private Product $ciment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\RolesSeeder::class);
        $this->seed(\Database\Seeders\PermissionSeeder::class);

        $this->admin = $this->user('Patron', User::ROLE_ADMIN);
        Place::create(['placeName' => 'Conakry', 'countryName' => 'Guinée', 'description' => 'test']);
        $this->madina = Store::create(['store_name' => 'Madina', 'place_id' => Place::firstOrFail()->id,
            'user_id' => $this->admin->id, 'store_picture' => '', 'description' => 'x', 'status' => 1, 'type' => 'magasin']);

        $this->vendeur = $this->user('Awa', User::ROLE_STORE_MANAGER, $this->madina->id);
        $this->magasinier = $this->user('Sekou', $this->roleId('magasinier'), $this->madina->id);
        $this->comptable = $this->user('Fatou', $this->roleId('comptable'));

        $this->client = Customer::create(['customerName' => 'Client X', 'mark' => 'CLX', 'tel' => '620000000', 'address' => 'Madina']);
        $this->ciment = Product::create(['libelle' => 'Ciment', 'sku' => 'CIM', 'description' => 'x', 'price_sale' => 45000, 'price' => 60000]);
    }

    private function roleId(string $slug): int
    {
        return (int) Role::where('slug', $slug)->value('id');
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

    private function depense(): Expense
    {
        $categorie = ExpenseCategory::firstWhere('slug', 'transport')
            ?? ExpenseCategory::forceCreate(['slug' => 'transport', 'categoryName' => 'Transport']);

        return Expense::forceCreate(['reference' => 'D-'.uniqid(), 'expense_categories_id' => $categorie->id,
            'store_id' => $this->madina->id, 'user_id' => $this->admin->id, 'amount' => 1000, 'exp_mode' => 'others', 'description' => 'x']);
    }

    private function devis(): Devi
    {
        return Devi::create(['numero_devis' => 'DEV-'.uniqid(), 'store_id' => $this->madina->id,
            'customer_id' => $this->client->id, 'total_amount' => 0, 'status' => 'draft', 'created_by' => $this->admin->id]);
    }

    // -----------------------------------------------------------------
    // Le rôle qui doit garder l'accès l'a toujours
    // -----------------------------------------------------------------

    public function test_le_vendeur_garde_ses_ecrans(): void
    {
        foreach (['customers.index', 'factures.index', 'devis.index', 'devis.create', 'expenses.index', 'expenses.create'] as $route) {
            $this->actingAs($this->vendeur)->get(route($route))->assertOk();
        }
    }

    public function test_le_comptable_garde_les_depenses_et_la_consultation(): void
    {
        foreach (['customers.index', 'factures.index', 'devis.index', 'expenses.index', 'expenses.create'] as $route) {
            $this->actingAs($this->comptable)->get(route($route))->assertOk();
        }
    }

    // -----------------------------------------------------------------
    // Le rôle sans la permission est refusé
    // -----------------------------------------------------------------

    public function test_un_magasinier_n_atteint_ni_les_clients_ni_les_ventes_ni_les_devis_ni_les_depenses(): void
    {
        foreach (['customers.index', 'customers.create', 'factures.index', 'devis.index', 'devis.create', 'expenses.index', 'expensesCategory.index'] as $route) {
            $this->actingAs($this->magasinier)->get(route($route))->assertForbidden();
        }
    }

    public function test_un_magasinier_ne_cree_ni_ne_supprime_un_client(): void
    {
        $this->actingAs($this->magasinier)
            ->post(route('customers.store'), ['customerName' => 'Intrus', 'tel' => '620111111', 'address' => 'x'])
            ->assertForbidden();

        $this->actingAs($this->magasinier)->delete(route('customers.destroy', $this->client->id))->assertForbidden();
        $this->actingAs($this->magasinier)->delete(route('customers.forceDelete', $this->client->id))->assertForbidden();

        $this->assertNotNull($this->client->fresh(), 'Le client a été supprimé par un rôle sans la permission.');
    }

    public function test_un_magasinier_ne_valide_pas_un_devis(): void
    {
        $devis = $this->devis();

        $this->actingAs($this->magasinier)->post(route('devis.validate', $devis->id))->assertForbidden();
        $this->actingAs($this->magasinier)->delete(route('devis.destroy', $devis->id))->assertForbidden();

        $this->assertSame('draft', $devis->fresh()->status);
    }

    /**
     * Supprimer une dépense efface une écriture financière : réservé à la
     * direction et à la comptabilité. Le vendeur saisit et corrige, il
     * n'efface pas.
     */
    public function test_la_suppression_d_une_depense_est_reservee_a_la_comptabilite(): void
    {
        $depense = $this->depense();
        $this->actingAs($this->vendeur)->delete(route('expenses.destroy', $depense->id))->assertForbidden();
        $this->assertNotNull($depense->fresh(), 'Le vendeur a pu supprimer une dépense.');

        $this->actingAs($this->comptable)->delete(route('expenses.destroy', $depense->id))->assertRedirect();
        $this->assertNull($depense->fresh(), 'Le comptable n\'a pas pu supprimer la dépense.');
    }

    /**
     * La suppression définitive réussissait, puis redirigeait vers une page
     * « corbeille » qui n'existe pas : l'utilisateur recevait une erreur 500
     * juste après une opération irréversible, sans savoir si elle avait eu lieu.
     * Le bloc catch visait la même route et masquait les vraies erreurs.
     */
    public function test_la_suppression_definitive_d_un_client_aboutit_sans_erreur(): void
    {
        $client = Customer::create(['customerName' => 'A supprimer', 'mark' => 'SUP', 'tel' => '620222222', 'address' => 'x']);
        $facture = \App\Models\Facture::create(['numero_facture' => 'F-SUP-1', 'store_id' => $this->madina->id,
            'customer_id' => $client->id, 'montant_total' => 0, 'avance' => 0, 'reste' => 0]);
        $facture->delete(); // archivée : elle reste rattachée au client

        $client->delete();

        $this->actingAs($this->admin)
            ->delete(route('customers.forceDelete', $client->id))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('success');

        $this->assertNull(Customer::withTrashed()->find($client->id));
        $this->assertNull(
            \App\Models\Facture::withTrashed()->find($facture->id),
            'La facture est restée en base avec un client_id qui ne pointe plus sur rien.'
        );
    }

    /**
     * Le menu ne doit pas proposer un écran que le serveur refusera.
     */
    public function test_le_menu_depenses_n_apparait_pas_sans_la_permission(): void
    {
        $this->actingAs($this->vendeur)->get(route('expenses.index'))->assertOk()->assertSee('Liste des dépenses');

        $this->actingAs($this->magasinier)->get(route('stock.index'))->assertOk()->assertDontSee('Liste des dépenses');
    }
}
