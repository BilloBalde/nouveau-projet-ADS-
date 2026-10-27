<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Support\ModeDeVente;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Ce commerçant n'aura jamais de boutique en ligne : la vitrine publique —
 * accueil, catalogue, rayons, fiches produits, à propos, contact — n'a pas
 * d'objet. Laissée ouverte, elle expose un catalogue et un formulaire de
 * contact que personne ne relèvera.
 *
 * La racine du site redirige vers l'espace de travail plutôt que de refuser :
 * c'est l'adresse que le gérant tapera.
 *
 * Les deux sens sont vérifiés : l'installation en mode caisse garde sa vitrine
 * intacte.
 */
class VitrinePubliqueFermeeTest extends TestCase
{
    use RefreshDatabase;

    /** Pages de la vitrine, hors racine qui a son propre traitement. */
    private const PAGES = ['about', 'contact', 'products.index', 'public.categories'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesSeeder::class);
        $this->seed(PermissionSeeder::class);
        Company::create(['name' => 'THB BANTIGUEL', 'address' => 'Madina', 'about' => 'x']);
    }

    private function admin(): User
    {
        return User::create([
            'name' => 'Patron', 'email' => 'patron@example.com', 'username' => 'patron',
            'password' => Hash::make('password'), 'role_id' => User::ROLE_ADMIN,
            'phone' => '620', 'status' => 'Active', 'token' => '',
        ])->fresh();
    }

    // -----------------------------------------------------------------
    // Mode inventaire : la vitrine est fermée
    // -----------------------------------------------------------------

    public function test_les_pages_de_la_vitrine_sont_fermees(): void
    {
        config(['sales.mode' => ModeDeVente::INVENTAIRE]);

        foreach (self::PAGES as $route) {
            $this->get(route($route))->assertForbidden();
        }
    }

    public function test_la_racine_mene_a_l_espace_de_travail(): void
    {
        config(['sales.mode' => ModeDeVente::INVENTAIRE]);

        $this->get('/')->assertRedirect(route('login'));

        $this->actingAs($this->admin())->get('/')->assertRedirect(route('home'));
    }

    public function test_le_formulaire_de_contact_n_accepte_plus_rien(): void
    {
        config(['sales.mode' => ModeDeVente::INVENTAIRE]);

        $this->post(route('contact.send'), [
            'name' => 'Intrus', 'email' => 'x@example.com', 'message' => 'bonjour',
        ])->assertForbidden();
    }

    public function test_le_menu_ne_propose_plus_de_voir_le_site(): void
    {
        config(['sales.mode' => ModeDeVente::INVENTAIRE]);

        $this->actingAs($this->admin())->get(route('home'))->assertOk()->assertDontSee('Voir Site');
    }

    // -----------------------------------------------------------------
    // Mode caisse : rien ne bouge pour les clients déjà livrés
    // -----------------------------------------------------------------

    public function test_en_mode_caisse_la_vitrine_reste_ouverte(): void
    {
        config(['sales.mode' => ModeDeVente::CAISSE]);

        $this->get('/')->assertOk();

        foreach (self::PAGES as $route) {
            $this->get(route($route))->assertOk();
        }
    }

    public function test_en_mode_caisse_le_menu_propose_toujours_le_site(): void
    {
        config(['sales.mode' => ModeDeVente::CAISSE]);

        $this->actingAs($this->admin())->get(route('home'))->assertOk()->assertSee('Voir Site');
    }
}
