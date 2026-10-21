<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * Le chemin d'installation documenté dans LIVRAISON.md.
 *
 *     php artisan migrate
 *     php artisan db:seed
 *
 * Aucun test ne l'exécutait : les tests appelaient RolesSeeder et
 * PermissionSeeder un par un, jamais la chaîne complète. Un seeder pouvait
 * donc rester cassé sans que rien ne le signale — c'est arrivé : ProductsSeeder
 * écrivait encore products.pcs après la suppression de cette colonne, et
 * « php artisan db:seed » échouait sur toute base neuve. C'est-à-dire sur
 * chaque livraison à un nouveau client.
 *
 * Ce test est le garde-fou de la mise en service.
 */
class InstallationTest extends TestCase
{
    use RefreshDatabase;

    public function test_le_seeder_complet_prepare_une_base_utilisable(): void
    {
        Artisan::call('db:seed', ['--force' => true]);

        // Rôles et permissions
        $this->assertGreaterThanOrEqual(6, Role::count(), 'Les rôles de base doivent être créés.');
        foreach (['admin', 'superuser', 'user', 'customer', 'comptable', 'achat'] as $slug) {
            $this->assertNotNull(Role::where('slug', $slug)->first(), "Rôle manquant : $slug");
        }
        $this->assertGreaterThan(30, Permission::count(), 'Les permissions doivent être créées.');

        // Les nouvelles permissions sont bien du voyage.
        foreach (['devis.view', 'devis.validate', 'expenses.view', 'expenses.delete', 'stock.adjust'] as $slug) {
            $this->assertNotNull(Permission::where('slug', $slug)->first(), "Permission manquante : $slug");
        }

        // Fiche entreprise, comptes, boutiques, catalogue
        $this->assertNotNull(Company::current(), 'La fiche entreprise doit être créée.');
        $this->assertNotNull(User::where('email', 'admin@example.com')->first(), 'Le compte administrateur doit exister.');
        $this->assertGreaterThan(0, Store::count(), 'Au moins une boutique doit être créée.');
        $this->assertGreaterThan(0, Product::count(), 'Le catalogue de démonstration doit être créé.');

        // Les prix de vente du catalogue suivent la marge configurée.
        $produit = Product::whereNotNull('price_sale')->where('price_sale', '>', 0)->first();
        $this->assertEquals(
            Product::prixDeVenteConseille((float) $produit->price_sale),
            (float) $produit->price,
            'Le prix de vente des produits de démonstration doit suivre la marge configurée.'
        );
    }

    /**
     * Les seeders sont annoncés idempotents dans LIVRAISON.md : les relancer ne
     * doit créer aucun doublon. C'est ce qui permet de reprendre les nouvelles
     * permissions sur une base déjà en service.
     */
    public function test_relancer_le_seeder_ne_cree_pas_de_doublon(): void
    {
        Artisan::call('db:seed', ['--force' => true]);

        $compteurs = [
            'roles' => Role::count(),
            'permissions' => Permission::count(),
            'produits' => Product::count(),
            'boutiques' => Store::count(),
            'comptes' => User::count(),
        ];

        Artisan::call('db:seed', ['--force' => true]);

        $this->assertSame($compteurs, [
            'roles' => Role::count(),
            'permissions' => Permission::count(),
            'produits' => Product::count(),
            'boutiques' => Store::count(),
            'comptes' => User::count(),
        ], 'Relancer le seeder a créé des doublons.');
    }

    /**
     * Une base fraîchement installée doit pouvoir servir : l'administrateur se
     * connecte et atteint son tableau de bord.
     */
    public function test_l_administrateur_peut_se_connecter_sur_une_base_neuve(): void
    {
        Artisan::call('db:seed', ['--force' => true]);

        $this->post(route('login_submit'), [
            'email' => 'admin@example.com',
            'password' => 'Admin@123',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticated();
        $this->get(route('home'))->assertOk();
    }
}
