<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetClientData extends Command
{
    protected $signature = 'client:reset-data {--force : Ne pas demander de confirmation}';

    protected $description = 'Vide les données métier (produits, clients, boutiques, stocks, ventes, achats, devis, factures, paiements, dépenses) pour repartir sur une base propre avec un nouveau client. Conserve comptes utilisateurs, rôles/permissions et paramètres.';

    /**
     * Ordre respecté pour ne pas violer les contraintes RESTRICT/NO ACTION
     * (ex: devis.customer_id, devis.store_id) même si FOREIGN_KEY_CHECKS
     * est désactivé le temps de l'opération.
     */
    private const TABLES = [
        'inventory_session_lines',
        'inventory_sessions',
        'partner_settlements',
        'fund_transactions',
        'funds',
        'devi_lines',
        'devis',
        'purchase_order_items',
        'purchase_orders',
        'order_items',
        'orders',
        'delivery_addresses',
        'debt_reminders',
        'payments',
        'transaction_factures',
        'factures',
        'sales',
        'partners',
        'purchases',
        'ligne_commandes',
        'stock_transfers',
        'stock_histories',
        'stock_movements',
        'store_products',
        'category_products',
        'expenses',
        'expense_categories',
        'products',
        'categories',
        'paiement_journaliers',
        'journaliers',
        'logistics',
        'contact_messages',
        'otp_codes',
        'notifications',
        'customers',
        'store_assignments',
        'stores',
    ];

    /**
     * Tables volontairement conservées : comptes, droits, fiche entreprise,
     * réglages et tables techniques de Laravel.
     *
     * Cette liste n'est pas décorative : avec TABLES, elle doit couvrir toute
     * la base. Une table qui n'est dans aucune des deux est une table oubliée,
     * et ResetClientDataTest le signale. Le défaut est arrivé — le module de
     * comptage, les fonds d'associés et le journal des mouvements de stock ont
     * été ajoutés après cette commande et n'y figuraient pas : la base du
     * nouveau client aurait gardé l'historique de l'ancien.
     */
    public const CONSERVEES = [
        'users',
        'roles',
        'permissions',
        'role_permissions',
        'user_permissions',
        'companies',
        'settings',
        'currency_settings',
        'payment_settings',
        'places',
        'migrations',
        'password_resets',
        'password_reset_tokens',
        'sessions',
        'failed_jobs',
        'personal_access_tokens',
        'jobs',
        'job_batches',
        'cache',
        'cache_locks',
    ];

    public function handle(): int
    {
        $this->warn('Cette commande va vider les tables suivantes :');
        $this->line(implode(', ', self::TABLES));
        $this->warn('Comptes utilisateurs, rôles/permissions, entreprise et paramètres (devise, paiement) sont conservés.');

        if (! $this->option('force') && ! $this->confirm('Confirmer la suppression ?')) {
            $this->info('Annulé.');

            return self::SUCCESS;
        }

        Schema::disableForeignKeyConstraints();

        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $count = DB::table($table)->count();
            DB::table($table)->truncate();
            $this->info("{$table}: {$count} ligne(s) supprimée(s).");
        }

        Schema::enableForeignKeyConstraints();

        // TRUNCATE ignore les règles ON DELETE (SET NULL inclus) : les comptes
        // encore liés à une boutique supprimée doivent être nettoyés à la main.
        $orphaned = DB::table('users')->whereNotNull('store_id')->update(['store_id' => null]);
        if ($orphaned > 0) {
            $this->info("users.store_id: {$orphaned} référence(s) orpheline(s) nettoyée(s).");
        }

        $this->info('Reset terminé.');

        return self::SUCCESS;
    }
}
