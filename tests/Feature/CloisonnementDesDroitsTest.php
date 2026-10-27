<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Place;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Trois trous relevés en interrogeant l'application avec une vraie session de
 * magasinier — le rôle le moins doté, neuf permissions, toutes sur le stock et
 * les transferts.
 *
 * Ils se ressemblent : l'écran était masqué dans la vue, ce qui n'empêche
 * personne d'envoyer la requête. Masquer n'est pas interdire.
 */
class CloisonnementDesDroitsTest extends TestCase
{
    use RefreshDatabase;

    private User $magasinier;

    private User $admin;

    private Store $boutique;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->seed(PermissionSeeder::class);

        Company::create([
            'name' => 'THB BANTIGUEL', 'address' => 'Madina', 'about' => 'x',
            'email' => 'contact@example.com', 'phone' => '628 263 636',
        ]);

        $this->admin = $this->compte('Patron', User::ROLE_ADMIN);
        $lieu = Place::create(['placeName' => 'Conakry', 'countryName' => 'Guinée', 'description' => 'x']);
        $this->boutique = Store::create([
            'store_name' => 'Boutique', 'place_id' => $lieu->id, 'user_id' => $this->admin->id,
            'store_picture' => '', 'description' => 'x', 'status' => 1, 'type' => Store::TYPE_MAGASIN,
        ]);

        $this->magasinier = $this->compte('Sekou', (int) Role::where('slug', 'magasinier')->value('id'));
        // Un employé sans affectation est renvoyé de ses propres écrans : ce
        // n'est pas le cas à tester ici.
        $this->magasinier->assignToStore($this->boutique->id);
        $this->magasinier = $this->magasinier->fresh();
    }

    private function compte(string $nom, int $role): User
    {
        return User::create([
            'name' => $nom, 'email' => str()->slug($nom).'@example.com', 'username' => str()->slug($nom),
            'password' => Hash::make('password'), 'role_id' => $role,
            'phone' => '620', 'status' => 'Active', 'token' => '',
        ])->fresh();
    }

    // -----------------------------------------------------------------
    // 1. La fiche entreprise
    // -----------------------------------------------------------------

    /**
     * Le nom de l'entreprise s'imprime sur chaque facture et chaque PDF.
     * N'importe quel compte connecté pouvait le réécrire : le formulaire était
     * caché dans la vue, la route ne vérifiait rien.
     */
    public function test_un_magasinier_ne_peut_pas_renommer_l_entreprise(): void
    {
        $this->actingAs($this->magasinier)
            ->post(route('companyCreate'), [
                'id' => Company::first()->id, 'name' => 'PIRATE',
                'address' => 'x', 'about' => 'x',
            ])->assertForbidden();

        $this->assertSame('THB BANTIGUEL', Company::current()->name, 'La fiche entreprise a été réécrite.');
    }

    public function test_l_administrateur_garde_la_main_sur_la_fiche(): void
    {
        $this->actingAs($this->admin)
            ->post(route('companyCreate'), [
                'id' => Company::first()->id, 'name' => 'THB BANTIGUEL SARL',
                'address' => 'Madina', 'about' => 'x',
            ])->assertRedirect();

        $this->assertSame('THB BANTIGUEL SARL', Company::current()->name);
    }

    // -----------------------------------------------------------------
    // 2. Les écrans qui portent des montants
    // -----------------------------------------------------------------

    /**
     * Chiffre d'affaires, marges, dettes, exports de ventes, catalogue complet :
     * tout cela s'ouvrait à un magasinier.
     */
    public function test_un_magasinier_n_atteint_aucun_ecran_de_chiffres(): void
    {
        $refuses = [
            'reports.daily', 'reports.daily.export.pdf', 'reports.sales',
            'places.index', 'places.create', 'categories.create',
            'emailSetting', 'contact.messages',
        ];

        foreach ($refuses as $route) {
            if (! Route::has($route)) {
                continue;
            }
            $this->actingAs($this->magasinier)->get(route($route))
                ->assertStatus(403, "Écran encore ouvert au magasinier : $route");
        }
    }

    public function test_un_magasinier_ne_cree_pas_de_lieu(): void
    {
        $this->actingAs($this->magasinier)
            ->post(route('places.store'), ['placeName' => 'Intrus', 'countryName' => 'Guinée', 'description' => 'x'])
            ->assertForbidden();

        $this->assertFalse(Place::where('placeName', 'Intrus')->exists());
    }

    /** Et l'administrateur, lui, passe partout : le verrou ne doit rien bloquer d'utile. */
    public function test_l_administrateur_atteint_toujours_ces_ecrans(): void
    {
        foreach (['reports.daily', 'reports.sales', 'places.index', 'categories.index', 'emailSetting'] as $route) {
            $this->actingAs($this->admin)->get(route($route))->assertOk();
        }
    }

    /**
     * Ce qui reste ouvert, et pourquoi : le magasinier détient « products.view ».
     * La liste des catégories et les exports de produits ne portent ni prix
     * d'achat ni marge — vérifié en les ouvrant avec son compte. Les lui
     * refuser l'empêcherait de travailler sans rien protéger.
     */
    public function test_ce_qui_reste_ouvert_au_magasinier_ne_porte_aucun_prix_d_achat(): void
    {
        foreach (['categories.index', 'exports.products.html'] as $route) {
            $page = $this->actingAs($this->magasinier)->get(route($route))->assertOk();
            $page->assertDontSee('Prix de revient');
            $page->assertDontSee("Prix d'achat");
        }
    }

    /** Le magasinier garde évidemment ses propres écrans. */
    public function test_le_magasinier_garde_le_stock_et_les_transferts(): void
    {
        foreach (['stock.index', 'stock.movements', 'transfers.index', 'produits.index', 'home'] as $route) {
            $this->actingAs($this->magasinier)->get(route($route))
                ->assertStatus(200, "Écran refusé à tort au magasinier : $route");
        }
    }

    // -----------------------------------------------------------------
    // 3. Les téléversements
    // -----------------------------------------------------------------

    /**
     * L'extension venait du navigateur et le fichier atterrissait dans un
     * dossier servi publiquement : un .php téléversé là s'exécute.
     */
    public function test_un_fichier_php_est_refuse_a_la_creation_d_un_compte(): void
    {
        $reponse = $this->actingAs($this->admin)->post(route('enregistrer'), [
            'name' => 'Intrus', 'email' => 'intrus@example.com', 'username' => 'intrus',
            'password' => 'MotDePasse1', 'password_confirmation' => 'MotDePasse1',
            'phone' => '620000000', 'role_id' => User::ROLE_ADMIN,
            'profilePic' => UploadedFile::fake()->createWithContent('porte.php', '<?php echo 1;'),
        ]);

        $reponse->assertSessionHasErrors('profilePic');
        $this->assertFileDoesNotExist(public_path('avatars/porte.php'));
    }

    public function test_un_fichier_php_est_refuse_a_la_modification_d_une_boutique(): void
    {
        $boutique = $this->boutique;

        $this->actingAs($this->admin)->put(route('boutiques.update', $boutique->id), [
            'store_name' => 'Boutique', 'place_id' => $boutique->place_id, 'user_id' => $this->admin->id,
            'status' => '1', 'description' => 'x', 'type' => Store::TYPE_MAGASIN,
            'store_picture' => UploadedFile::fake()->createWithContent('porte.php', '<?php echo 1;'),
        ])->assertSessionHasErrors('store_picture');

        $this->assertFileDoesNotExist(public_path('stores/porte.php'));
    }
}
