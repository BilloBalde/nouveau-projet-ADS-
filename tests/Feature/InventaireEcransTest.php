<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\InventorySession;
use App\Models\Place;
use App\Models\Product;
use App\Models\Role;
use App\Models\Store;
use App\Models\StoreProduct;
use App\Models\User;
use App\Services\InventaireService;
use App\Support\ModeDeVente;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Parcours du gérant en mode « inventaire » : il ouvre un comptage, imprime
 * la feuille, compte dans la boutique, saisit au bureau, lit son chiffre
 * d'affaires.
 *
 * Vérifie aussi les deux côtés du réglage de mode : les écrans d'inventaire
 * n'existent que là où ils ont un sens, et la caisse est fermée là où les
 * ventes ne se saisissent pas.
 */
class InventaireEcransTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Store $madina;

    private Product $ciment;

    protected function setUp(): void
    {
        parent::setUp();

        // Les scénarios de comptage portent des dates fixes. Sans horloge
        // figée, elles basculent dans le futur avec le temps qui passe — et un
        // comptage daté du futur est désormais refusé, à juste titre.
        $this->travelTo('2027-12-31 12:00:00');

        $this->seed(RolesSeeder::class);
        $this->seed(PermissionSeeder::class);

        config(['sales.mode' => ModeDeVente::INVENTAIRE]);

        $this->admin = $this->user('Patron', User::ROLE_ADMIN);
        Place::create(['placeName' => 'Conakry', 'countryName' => 'Guinée', 'description' => 'test']);
        $this->madina = Store::create(['store_name' => 'Madina', 'place_id' => Place::firstOrFail()->id,
            'user_id' => $this->admin->id, 'store_picture' => '', 'description' => 'x', 'status' => 1,
            'type' => 'magasin', 'is_main' => true]);

        $this->ciment = Product::create(['libelle' => 'Ciment', 'sku' => 'CIM', 'description' => 'x',
            'unite' => 'pcs', 'price' => 50_000, 'price_sale' => 38_000, 'price_manuel' => true]);
        StoreProduct::create(['store_id' => $this->madina->id, 'product_id' => $this->ciment->id, 'quantity' => 100]);
    }

    private function user(string $name, int|string $role, ?int $storeId = null): User
    {
        $roleId = is_int($role) ? $role : (int) Role::where('slug', $role)->value('id');

        $user = User::create([
            'name' => $name, 'email' => str()->slug($name).uniqid().'@example.com', 'username' => str()->slug($name).uniqid(),
            'password' => Hash::make('password'), 'role_id' => $roleId, 'phone' => '620', 'status' => 'Active', 'token' => '',
        ]);

        if ($storeId) {
            $user->assignToStore($storeId);
        }

        return $user->fresh();
    }

    // -----------------------------------------------------------------
    // Le parcours
    // -----------------------------------------------------------------

    public function test_le_parcours_complet_du_gerant(): void
    {
        $this->actingAs($this->admin);

        // 1. Ouvrir le comptage : on arrive sur la feuille à imprimer.
        $this->get(route('inventaires.create'))->assertOk();
        $this->post(route('inventaires.store'), [
            'store_id' => $this->madina->id,
            'counted_on' => '2026-01-01',
        ])->assertRedirect();

        $premier = InventorySession::sole();
        $this->assertSame(InventorySession::BROUILLON, $premier->status);

        // 2. La feuille sort bien en PDF.
        $feuille = $this->get(route('inventaires.feuille', $premier->id))->assertOk();
        $this->assertSame('application/pdf', $feuille->headers->get('content-type'));

        // 3. Saisie du premier comptage : il pose le stock de départ.
        $this->post(route('inventaires.valider', $premier->id), [
            'lignes' => [['product_id' => $this->ciment->id, 'counted' => 100]],
        ])->assertRedirect(route('inventaires.show', $premier->id));

        $this->assertEquals(0, $premier->fresh()->total_revenue_gnf, 'Un premier comptage ne déduit aucune vente.');

        // 4. Un mois plus tard : 30 restants sur 100.
        $this->post(route('inventaires.store'), [
            'store_id' => $this->madina->id,
            'counted_on' => '2026-01-31',
        ])->assertRedirect();

        $second = InventorySession::where('id', '!=', $premier->id)->sole();
        $this->get(route('inventaires.edit', $second->id))->assertOk()->assertSee('Ciment');

        $this->post(route('inventaires.valider', $second->id), [
            'lignes' => [['product_id' => $this->ciment->id, 'counted' => 30]],
        ])->assertRedirect(route('inventaires.show', $second->id))->assertSessionHas('success');

        // 5. Le rapport affiche le chiffre d'affaires, et chaque terme du calcul.
        $this->get(route('inventaires.show', $second->id))
            ->assertOk()
            ->assertSee('3 500 000')   // 70 × 50 000
            ->assertSee('Ciment');

        $this->assertEquals(3_500_000, $second->fresh()->total_revenue_gnf);

        // 6. Le rapport s'imprime.
        $rapport = $this->get(route('inventaires.rapport-pdf', $second->id))->assertOk();
        $this->assertSame('application/pdf', $rapport->headers->get('content-type'));

        // 7. La liste montre les deux comptages.
        $this->get(route('inventaires.index'))->assertOk()->assertSee($second->reference);
    }

    /**
     * La feuille imprimée ne doit pas révéler le stock attendu : le compteur
     * recopierait au lieu de compter, et l'inventaire ne révélerait plus rien.
     */
    public function test_la_feuille_imprimee_ne_montre_pas_le_stock_theorique(): void
    {
        $this->actingAs($this->admin);
        $seance = app(InventaireService::class)->preparer($this->madina->id, '2026-02-01');

        $reponse = $this->get(route('inventaires.feuille', $seance->id))->assertOk();
        $this->assertSame('application/pdf', $reponse->headers->get('content-type'));

        // Un PDF n'est pas lisible tel quel : on vérifie la vue qui le compose.
        $vue = view('inventaires.feuille', [
            'seance' => $seance->load('store', 'previousSession'),
            'produits' => Product::whereKey($this->ciment->id)->get(['id', 'libelle', 'sku', 'unite']),
            'company' => null,
        ])->render();

        $this->assertStringContainsString('Ciment', $vue);
        $this->assertStringContainsString('Quantité comptée', $vue);

        // La case de comptage est vide : c'est au compteur de l'écrire.
        // (Chercher l'absence de « 100 » serait trompeur : la largeur CSS vaut
        // 100%, et un produit pourrait s'appeler « Ciment 100 kg ».)
        $this->assertStringContainsString('<td class="saisie"></td>', $vue, 'La case de comptage doit être vide.');
        $this->assertStringNotContainsString('Théorique', $vue, 'La feuille ne doit pas annoncer le stock attendu.');
        $this->assertStringNotContainsString('data-theorique', $vue);
        $this->assertStringContainsString('comptez ce que vous voyez', $vue);

        // Le même produit, à la saisie, montre bien le théorique : c'est la
        // différence voulue entre la feuille et l'écran.
        $this->get(route('inventaires.edit', $seance->id))->assertOk()->assertSee('Théorique');
    }

    /** Le stock théorique, lui, s'affiche à la saisie au bureau. */
    public function test_l_ecran_de_saisie_montre_le_stock_theorique(): void
    {
        $this->actingAs($this->admin);
        app(InventaireService::class)->preparer($this->madina->id, '2026-02-01');
        $seance = InventorySession::sole();

        $this->get(route('inventaires.edit', $seance->id))
            ->assertOk()
            ->assertSee('Théorique')
            ->assertSee('100');
    }

    /**
     * Une case laissée vide veut dire « non compté » : le stock du produit ne
     * doit pas être touché, et aucune vente ne doit en être déduite. Compter
     * zéro se dit en écrivant 0.
     */
    public function test_une_case_vide_ne_vaut_pas_zero(): void
    {
        $this->actingAs($this->admin);
        $autre = Product::create(['libelle' => 'Sucre', 'sku' => 'SUC', 'description' => 'x',
            'unite' => 'carton', 'price' => 12_000, 'price_sale' => 9_000, 'price_manuel' => true]);
        StoreProduct::create(['store_id' => $this->madina->id, 'product_id' => $autre->id, 'quantity' => 40]);

        $service = app(InventaireService::class);
        $service->valider($service->preparer($this->madina->id, '2026-03-01'), [
            ['product_id' => $this->ciment->id, 'counted' => 100],
            ['product_id' => $autre->id, 'counted' => 40],
        ]);

        $seance = $service->preparer($this->madina->id, '2026-03-31');
        $this->post(route('inventaires.valider', $seance->id), [
            'lignes' => [
                ['product_id' => $this->ciment->id, 'counted' => 60],
                ['product_id' => $autre->id, 'counted' => ''],   // non compté
            ],
        ])->assertRedirect();

        $seance->refresh()->load('lines');
        $this->assertCount(1, $seance->lines, 'Seul le produit compté doit avoir une ligne.');
        $this->assertSame($this->ciment->id, (int) $seance->lines->first()->product_id);

        // Le stock du produit non compté est intact.
        $this->assertSame(40, (int) StoreProduct::where('store_id', $this->madina->id)
            ->where('product_id', $autre->id)->value('quantity'));
    }

    public function test_un_comptage_valide_ne_se_supprime_pas(): void
    {
        $this->actingAs($this->admin);
        $service = app(InventaireService::class);
        $seance = $service->valider($service->preparer($this->madina->id, '2026-04-01'), [
            ['product_id' => $this->ciment->id, 'counted' => 100],
        ]);

        $this->delete(route('inventaires.destroy', $seance->id))
            ->assertRedirect()
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'ne se supprime pas'));

        $this->assertNotNull($seance->fresh());
    }

    public function test_un_brouillon_se_supprime(): void
    {
        $this->actingAs($this->admin);
        $seance = app(InventaireService::class)->preparer($this->madina->id, '2026-05-01');

        $this->delete(route('inventaires.destroy', $seance->id))->assertRedirect(route('inventaires.index'));
        $this->assertNull($seance->fresh());
    }

    // -----------------------------------------------------------------
    // Périmètre et permissions
    // -----------------------------------------------------------------

    public function test_un_employe_ne_compte_pas_une_autre_boutique(): void
    {
        $kaloum = Store::create(['store_name' => 'Kaloum', 'place_id' => Place::firstOrFail()->id,
            'user_id' => $this->admin->id, 'store_picture' => '', 'description' => 'x', 'status' => 1, 'type' => 'magasin']);
        $vendeur = $this->user('Awa', 'responsable', $this->madina->id);

        $seance = app(InventaireService::class)->preparer($kaloum->id, '2026-06-01');

        $this->actingAs($vendeur)->get(route('inventaires.edit', $seance->id))->assertForbidden();
        $this->actingAs($vendeur)->get(route('inventaires.feuille', $seance->id))->assertForbidden();
        $this->actingAs($vendeur)->post(route('inventaires.store'), [
            'store_id' => $kaloum->id, 'counted_on' => '2026-06-02',
        ])->assertForbidden();
    }

    public function test_un_role_sans_droit_sur_le_stock_n_atteint_pas_les_inventaires(): void
    {
        $comptable = $this->user('Fatou', 'comptable');

        // Le comptable a stock.view mais pas stock.adjust : il lit, il ne compte pas.
        $this->actingAs($comptable)->get(route('inventaires.index'))->assertOk();
        $this->actingAs($comptable)->get(route('inventaires.create'))->assertForbidden();
    }

    // -----------------------------------------------------------------
    // Les deux côtés du réglage de mode
    // -----------------------------------------------------------------

    public function test_en_mode_caisse_les_ecrans_d_inventaire_sont_fermes(): void
    {
        config(['sales.mode' => ModeDeVente::CAISSE]);

        $this->actingAs($this->admin)->get(route('inventaires.index'))
            ->assertForbidden();
    }

    public function test_en_mode_inventaire_la_caisse_est_fermee(): void
    {
        $this->actingAs($this->admin);

        $this->get(route('pos'))->assertForbidden();
        $this->get(route('facturations.add'))->assertForbidden();
        $this->post(route('sales.store'), [])->assertForbidden();
    }

    public function test_en_mode_inventaire_le_menu_ne_propose_plus_la_caisse(): void
    {
        $reponse = $this->actingAs($this->admin)->get(route('stock.index'))->assertOk();

        $reponse->assertSee('Inventaires et chiffre d\'affaires', false);
        $reponse->assertDontSee('Liste des ventes');
        $reponse->assertDontSee(route('pos'), false);
    }

    public function test_en_mode_caisse_le_menu_reste_celui_d_avant(): void
    {
        config(['sales.mode' => ModeDeVente::CAISSE]);

        $reponse = $this->actingAs($this->admin)->get(route('stock.index'))->assertOk();

        $reponse->assertSee('Liste des ventes');
        $reponse->assertSee(route('pos'), false);
        $reponse->assertDontSee('Inventaires et chiffre d\'affaires', false);
    }

    /**
     * Premier comptage d'une installation neuve : la feuille doit porter tout
     * le catalogue.
     *
     * Elle ne listait que les produits ayant déjà une ligne de stock ou un
     * mouvement dans cette boutique. Sur une base neuve, aucun produit n'en a
     * : la feuille sortait presque vide et le gérant n'avait rien à compter,
     * donc aucun moyen de poser son stock de départ.
     */
    public function test_le_premier_comptage_porte_tout_le_catalogue(): void
    {
        // Jamais eu de stock ni de mouvement dans cette boutique.
        $jamaisEnStock = Product::create(['libelle' => 'Tôle', 'sku' => 'TOL', 'description' => 'x',
            'unite' => 'pcs', 'price' => 70_000, 'price_manuel' => true]);

        $this->actingAs($this->admin);
        $seance = app(InventaireService::class)->preparer($this->madina->id, '2026-07-01');

        $this->get(route('inventaires.edit', $seance->id))
            ->assertOk()
            ->assertSee('Tôle')      // le produit sans historique
            ->assertSee('Ciment');   // celui qui a du stock

        // Et il est réellement comptable : son stock de départ se pose.
        $this->post(route('inventaires.valider', $seance->id), [
            'lignes' => [
                ['product_id' => $this->ciment->id, 'counted' => 100],
                ['product_id' => $jamaisEnStock->id, 'counted' => 12],
            ],
        ])->assertRedirect();

        $this->assertSame(12, (int) StoreProduct::where('store_id', $this->madina->id)
            ->where('product_id', $jamaisEnStock->id)->value('quantity'));
    }

    /**
     * Les comptages suivants, eux, restent limités à ce qui a circulé dans la
     * boutique : inutile de faire chercher un produit qu'elle n'a jamais eu.
     */
    public function test_les_comptages_suivants_ne_listent_que_ce_qui_a_circule(): void
    {
        $this->actingAs($this->admin);
        $service = app(InventaireService::class);

        $service->valider($service->preparer($this->madina->id, '2026-08-01'), [
            ['product_id' => $this->ciment->id, 'counted' => 100],
        ]);

        // Produit ajouté au catalogue après le premier comptage, jamais livré ici.
        Product::create(['libelle' => 'Peinture', 'sku' => 'PEI', 'description' => 'x',
            'unite' => 'pcs', 'price' => 30_000, 'price_manuel' => true]);

        $seance = $service->preparer($this->madina->id, '2026-08-31');

        $this->get(route('inventaires.edit', $seance->id))
            ->assertOk()
            ->assertSee('Ciment')
            ->assertDontSee('Peinture');
    }

    /**
     * Le formulaire de saisie doit envoyer ses champs sous « lignes[0][...] ».
     *
     * Ils étaient nommés « lignes.0[counted] ». PHP transforme le point en
     * souligné à la réception : la requête arrivait avec un champ « lignes_0 »
     * et rien sous « lignes », donc la validation refusait toute la saisie.
     * Le gérant remplissait son tableau, cliquait, et retombait sur un écran
     * vide sans rien avoir enregistré.
     *
     * Les autres tests postent un tableau PHP directement : ils ne passent pas
     * par les noms de champs du HTML et ne pouvaient pas voir le problème.
     * Celui-ci relit le formulaire tel que le navigateur le lit.
     */
    public function test_le_formulaire_de_saisie_envoie_des_champs_exploitables(): void
    {
        $this->actingAs($this->admin);
        $seance = app(InventaireService::class)->preparer($this->madina->id, '2026-09-01');

        $html = $this->get(route('inventaires.edit', $seance->id))->assertOk()->getContent();

        // Les champs du formulaire, tels qu'un navigateur les enverrait.
        preg_match_all('/<input[^>]+name="([^"]+)"[^>]*value="([^"]*)"/', $html, $champs, PREG_SET_ORDER);
        $requete = [];
        foreach ($champs as [, $nom, $valeur]) {
            if (str_starts_with($nom, 'lignes')) {
                $requete[] = urlencode($nom).'='.urlencode($valeur);
            }
        }
        $this->assertNotEmpty($requete, 'Le formulaire ne porte aucun champ de ligne.');

        parse_str(implode('&', $requete), $recu);

        $this->assertArrayHasKey('lignes', $recu, 'Les champs n\'arrivent pas sous « lignes » : le point d\'un nom comme « lignes.0 » devient un souligné.');
        $this->assertIsArray($recu['lignes']);
        $premiere = reset($recu['lignes']);
        $this->assertArrayHasKey('product_id', $premiere);
        $this->assertSame((string) $this->ciment->id, (string) $premiere['product_id']);

        // Et la validation accepte bien cette forme.
        $recu['lignes'][array_key_first($recu['lignes'])]['counted'] = '42';
        $this->post(route('inventaires.valider', $seance->id), ['lignes' => $recu['lignes']])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('inventaires.show', $seance->id));
    }

    /**
     * Au premier comptage, la colonne « Début » montre le stock que
     * l'application tient déjà, pour que le gérant compare ce qu'il compte
     * avec ce qu'elle croyait avoir. Elle affichait zéro partout.
     */
    public function test_le_premier_comptage_affiche_le_stock_deja_enregistre(): void
    {
        $this->actingAs($this->admin);
        $seance = app(InventaireService::class)->preparer($this->madina->id, '2026-10-01');

        $termes = app(InventaireService::class)->termesAttendus($seance);
        $ligne = $termes[$this->ciment->id.'|'];

        $this->assertSame(100, $ligne['opening'], 'Le stock enregistré doit servir de point de départ affiché.');
        $this->assertSame(100, $ligne['theorique']);

        // Et il reste informatif : une première séance ne déduit aucune vente.
        $seance = app(InventaireService::class)->valider($seance, [
            ['product_id' => $this->ciment->id, 'counted' => 70],
        ]);
        $this->assertSame(0, (int) $seance->lines->sole()->sold_quantity);
        $this->assertEquals(0, $seance->total_revenue_gnf);
    }

    /**
     * Marche à suivre imprimable, à laisser au gérant près de sa caisse.
     *
     * Elle vit dans l'application pour rester d'accord avec la version
     * installée : une marche à suivre périmée est pire que pas de marche à
     * suivre du tout.
     */
    public function test_le_mode_operatoire_s_imprime(): void
    {
        $this->actingAs($this->admin);

        $reponse = $this->get(route('inventaires.mode-operatoire'))->assertOk();
        $this->assertSame('application/pdf', $reponse->headers->get('content-type'));

        // Le contenu, vérifié sur la vue : un PDF n'est pas relisible tel quel.
        $vue = view('inventaires.mode-operatoire', ['company' => Company::current()])->render();

        // Les quatre étapes et les trois règles qui décident de la justesse du chiffre.
        foreach (['Ouvrir le comptage', 'Imprimer la feuille', 'Compter en boutique', 'Saisir au bureau',
            'Saisissez tous vos achats', 'Passez par les transferts', 'Déclarez les pertes'] as $attendu) {
            $this->assertStringContainsString($attendu, $vue, "Section manquante : $attendu");
        }

        // Les trois pièges qui font qu'un gérant croit l'application cassée.
        $this->assertStringContainsString('premier comptage affiche 0', $vue);
        $this->assertStringContainsString('je n\'ai pas compté ce produit', $vue);
        $this->assertStringContainsString('ne se modifie plus', $vue);
    }

    /** Le bouton est à portée de main depuis la liste des comptages. */
    public function test_le_mode_operatoire_est_accessible_depuis_la_liste(): void
    {
        $this->actingAs($this->admin)->get(route('inventaires.index'))
            ->assertOk()
            ->assertSee(route('inventaires.mode-operatoire'), false)
            ->assertSee('Mode opératoire');
    }
}
