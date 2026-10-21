<?php

namespace Tests\Feature;

use App\Models\Place;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Une section, c'est un emplacement de premier niveau et tout ce qui lui est
 * rattaché. Le mécanisme sert à cloisonner l'application quand il y en a
 * plusieurs : le propriétaire « entre » dans une boutique et ne voit plus que
 * la sienne.
 *
 * Une installation qui tient une seule boutique — avec ses stocks internes et
 * son entrepôt rattachés — n'a rien à cloisonner. Le sélecteur de section et
 * l'écran « Sections » n'y ont pas d'objet : ils disparaissent.
 *
 * Ce test vérifie les DEUX sens. Le second compte autant que le premier : le
 * client livré qui tient plusieurs boutiques doit garder son sélecteur intact.
 */
class SectionUniqueTest extends TestCase
{
    use RefreshDatabase;

    private Place $lieu;

    private User $admin;

    private Store $boutique;

    private Store $entrepot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesSeeder::class);
        $this->seed(PermissionSeeder::class);

        $this->admin = $this->user('Patron', User::ROLE_ADMIN);
        $this->lieu = Place::create(['placeName' => 'Conakry', 'countryName' => 'Guinée', 'description' => 'test']);

        // La boutique du client : un magasin, son entrepôt et un stock interne
        // rattachés. Trois emplacements, mais une seule section.
        $this->boutique = $this->store('Boutique', ['is_main' => true]);
        $this->entrepot = $this->store('Entrepôt de la boutique', ['type' => Store::TYPE_ENTREPOT, 'parent_id' => $this->boutique->id]);
        $this->store('Réserve', ['type' => Store::TYPE_STOCK, 'parent_id' => $this->boutique->id]);
    }

    private function user(string $name, int|string $role, ?int $storeId = null): User
    {
        $roleId = is_int($role) ? $role : Role::where('slug', $role)->value('id');

        $user = User::create([
            'name' => $name, 'email' => str()->slug($name).uniqid().'@example.com', 'username' => str()->slug($name).uniqid(),
            'password' => Hash::make('password'), 'role_id' => $roleId, 'phone' => '620000001', 'status' => 'Active', 'token' => '',
        ]);

        if ($storeId) {
            $user->assignToStore($storeId);
        }

        return $user->fresh();
    }

    private function store(string $name, array $extra = []): Store
    {
        return Store::create($extra + [
            'store_name' => $name, 'place_id' => $this->lieu->id, 'user_id' => $this->admin->id,
            'store_picture' => '', 'description' => $name, 'status' => 1, 'type' => Store::TYPE_MAGASIN,
        ]);
    }

    /** Une deuxième boutique de premier niveau : la notion de section reprend son sens. */
    private function deuxiemeBoutique(): Store
    {
        return $this->store('Deuxième boutique');
    }

    // -----------------------------------------------------------------
    // Une seule boutique : plus de sections
    // -----------------------------------------------------------------

    public function test_les_emplacements_rattaches_ne_font_pas_plusieurs_sections(): void
    {
        $this->assertSame(3, Store::count(), 'Le décor doit compter trois emplacements.');
        $this->assertFalse(
            Store::plusieursSections(),
            'Un magasin avec son entrepôt et son stock interne rattachés ne forme qu\'une section.'
        );
    }

    public function test_le_menu_ne_propose_plus_les_sections(): void
    {
        $page = $this->actingAs($this->admin)->get(route('home'))->assertOk();

        $page->assertDontSee(route('sections.index'));
        $page->assertDontSee('Vue globale (toutes les sections)');
    }

    public function test_les_ecrans_de_sections_renvoient_au_tableau_de_bord(): void
    {
        $this->actingAs($this->admin)->get(route('sections.index'))->assertRedirect(route('home'));

        $this->actingAs($this->admin)
            ->post(route('sections.switch'), ['section_id' => $this->entrepot->id])
            ->assertRedirect(route('home'));

        $this->assertNull(session('section_id'), 'Aucune section ne doit pouvoir être choisie.');
    }

    /**
     * Le cas qui enfermait : une valeur restée en session — le propriétaire était
     * entré dans une section avant que les emplacements soient réorganisés sous
     * une seule boutique — cloisonnait toute l'application, sans sélecteur pour
     * en sortir puisqu'il a disparu.
     */
    public function test_une_section_restee_en_session_est_ignoree(): void
    {
        $this->actingAs($this->admin)
            ->withSession(['section_id' => $this->entrepot->id])
            ->get(route('home'))->assertOk();

        $this->assertNull(auth()->user()->activeSectionId(), 'La section en session doit être ignorée.');
        $this->assertFalse(auth()->user()->isStoreScoped(), 'Le propriétaire doit rester en vue globale.');
    }

    /**
     * L'employé rattaché à l'entrepôt ou à la réserve garde le nom de son
     * emplacement sous les yeux : avec une seule boutique, c'est la seule
     * indication de l'endroit où il travaille. Seul le lien vers l'écran
     * « Sections » tombe.
     */
    public function test_l_employe_rattache_garde_le_nom_de_son_emplacement(): void
    {
        $magasinier = $this->user('Sekou', 'magasinier', $this->entrepot->id);

        $this->actingAs($magasinier)->get(route('stock.index'))->assertOk()
            ->assertSee('Entrepôt de la boutique')
            ->assertDontSee(route('sections.index'));
    }

    // -----------------------------------------------------------------
    // Deux boutiques : tout revient
    // -----------------------------------------------------------------

    public function test_une_deuxieme_boutique_fait_revenir_le_selecteur(): void
    {
        $this->deuxiemeBoutique();

        $this->assertTrue(Store::plusieursSections());

        $this->actingAs($this->admin)->get(route('home'))->assertOk()
            ->assertSee(route('sections.index'))
            ->assertSee('Vue globale (toutes les sections)');
    }

    public function test_avec_deux_boutiques_le_cloisonnement_fonctionne_toujours(): void
    {
        $deuxieme = $this->deuxiemeBoutique();

        $this->actingAs($this->admin)->get(route('sections.index'))->assertOk();

        $this->actingAs($this->admin)
            ->post(route('sections.switch'), ['section_id' => $deuxieme->id])
            ->assertRedirect(route('home'));

        $this->assertSame($deuxieme->id, session('section_id'));

        $this->actingAs($this->admin)->get(route('home'))->assertOk();
        $this->assertSame($deuxieme->id, auth()->user()->activeSectionId());
        $this->assertTrue(auth()->user()->isStoreScoped());
    }
}
