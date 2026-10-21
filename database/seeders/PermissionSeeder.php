<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Idempotent : peut être relancé sans dupliquer.
     */
    public function run(): void
    {
        // ---- Permissions par module ----
        $modules = [
            'Tableau de bord' => [
                'dashboard.view' => 'Voir le tableau de bord',
            ],
            'Produits' => [
                'products.view'   => 'Voir les produits',
                'products.create' => 'Créer un produit',
                'products.edit'   => 'Modifier un produit',
                'products.delete' => 'Supprimer un produit',
            ],
            'Ventes' => [
                'sales.view'   => 'Voir les ventes',
                'sales.create' => 'Créer une vente',
                'sales.edit'   => 'Modifier une vente',
                'sales.delete' => 'Supprimer une vente',
            ],
            'Achats' => [
                'purchases.view'   => 'Voir les achats',
                'purchases.create' => 'Créer un achat',
                'purchases.edit'   => 'Modifier un achat',
                'purchases.delete' => 'Supprimer un achat',
            ],
            'Clients' => [
                'customers.view'   => 'Voir les clients',
                'customers.create' => 'Créer un client',
                'customers.edit'   => 'Modifier un client',
                'customers.delete' => 'Supprimer un client',
            ],
            'Paiements' => [
                'payments.view'   => 'Voir les paiements',
                'payments.create' => 'Enregistrer un paiement',
                'payments.edit'   => 'Modifier un paiement',
                'payments.delete' => 'Supprimer un paiement',
            ],
            // Un devis suit son propre circuit (brouillon → validé → facture) :
            // ses permissions sont distinctes de celles des ventes.
            'Devis' => [
                'devis.view'     => 'Voir les devis',
                'devis.create'   => 'Créer un devis',
                'devis.edit'     => 'Modifier un devis',
                'devis.delete'   => 'Supprimer un devis',
                'devis.validate' => 'Valider un devis (le transformer en facture)',
            ],
            // Une dépense est une sortie d'argent : la supprimer efface une
            // écriture financière, d'où une permission à part.
            'Dépenses' => [
                'expenses.view'   => 'Voir les dépenses',
                'expenses.create' => 'Enregistrer une dépense',
                'expenses.edit'   => 'Modifier une dépense',
                'expenses.delete' => 'Supprimer une dépense',
            ],
            'Rapports' => [
                'reports.view'   => 'Voir les rapports',
                'reports.export' => 'Exporter les rapports',
            ],
            'Utilisateurs' => [
                'users.view'   => 'Voir les utilisateurs',
                'users.create' => 'Créer un utilisateur',
                'users.edit'   => 'Modifier un utilisateur',
                'users.delete' => 'Supprimer un utilisateur',
            ],
            'Boutiques' => [
                'stores.view'   => 'Voir les boutiques',
                'stores.create' => 'Créer une boutique',
                'stores.edit'   => 'Modifier une boutique',
                'stores.delete' => 'Supprimer une boutique',
            ],
            'Commandes e-commerce' => [
                'orders.view'     => 'Voir les commandes',
                'orders.validate' => 'Valider/rejeter une commande',
                'orders.deliver'  => 'Livrer une commande',
            ],
            'Paramètres' => [
                'settings.view' => 'Voir les paramètres',
                'settings.edit' => 'Modifier les paramètres',
            ],
        ];

        foreach ($modules as $module => $permissions) {
            foreach ($permissions as $slug => $name) {
                Permission::updateOrCreate(
                    ['slug' => $slug],
                    ['name' => $name, 'module' => $module]
                );
            }
        }

        // ---- Nouveaux rôles ----
        $comptable = Role::updateOrCreate(['slug' => 'comptable'], ['nameRole' => 'Comptable']);
        $achat     = Role::updateOrCreate(['slug' => 'achat'], ['nameRole' => 'Responsable Achats']);

        // ---- Presets role_permissions (reflètent l'accès actuel — ne rien casser) ----
        $all = Permission::pluck('id', 'slug');

        $presets = [
            // admin (1) : toutes (bypass de toute façon dans hasPermission)
            1 => $all->keys()->all(),
            // superuser/manager (2) : tout sauf paramètres et suppression d'utilisateurs
            2 => $all->keys()->reject(fn ($s) => str_starts_with($s, 'settings.') || $s === 'users.delete')->all(),
            // gérant de boutique (3) : accès actuel (pas d'achats, pas d'utilisateurs, pas de boutiques)
            3 => [
                'dashboard.view',
                'products.view', 'products.create', 'products.edit',
                'sales.view', 'sales.create', 'sales.edit', 'sales.delete',
                'customers.view', 'customers.create', 'customers.edit', 'customers.delete',
                'payments.view', 'payments.create',
                'reports.view',
                'orders.view', 'orders.validate', 'orders.deliver', // validate : commandes attribuées à sa boutique uniquement
                'devis.view', 'devis.create', 'devis.edit', 'devis.delete', 'devis.validate',
                // Dépenses de sa boutique : il les saisit et les corrige ; les
                // supprimer reste réservé à la direction et à la comptabilité.
                'expenses.view', 'expenses.create', 'expenses.edit',
            ],
            // comptable : finances
            $comptable->id => [
                'dashboard.view',
                'payments.view', 'payments.create', 'payments.edit', 'payments.delete',
                'reports.view', 'reports.export',
                'sales.view', 'customers.view',
                'expenses.view', 'expenses.create', 'expenses.edit', 'expenses.delete',
                'devis.view',
            ],
            // responsable achats : approvisionnement
            $achat->id => [
                'dashboard.view',
                'purchases.view', 'purchases.create', 'purchases.edit', 'purchases.delete',
                'products.view', 'stores.view',
                // Consultation des dépenses : les frais rattachés aux achats
                // (transport, douane) se lisent depuis cet écran.
                'expenses.view',
            ],
        ];

        foreach ($presets as $roleId => $slugs) {
            foreach ($slugs as $slug) {
                if (! isset($all[$slug])) continue;
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $all[$slug]],
                    ['updated_at' => now(), 'created_at' => now()]
                );
            }
        }

        // Emplacements, stock, transferts + rôles Magasinier / Responsable magasin.
        // Même code que la migration 2026_09_29_000005 : une base neuve et une base
        // migrée aboutissent au même état.
        \App\Support\LocationPermissions::install();
    }
}
