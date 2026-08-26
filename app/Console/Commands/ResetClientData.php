<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetClientData extends Command
{
    protected $signature = 'client:reset-data {--force : Ne pas demander de confirmation}';

    protected $description = "Vide les données métier (produits, clients, boutiques, stocks, ventes, achats, devis, factures, paiements, dépenses) pour repartir sur une base propre avec un nouveau client. Conserve comptes utilisateurs, rôles/permissions et paramètres.";

    /**
     * Ordre respecté pour ne pas violer les contraintes RESTRICT/NO ACTION
     * (ex: devis.customer_id, devis.store_id) même si FOREIGN_KEY_CHECKS
     * est désactivé le temps de l'opération.
     */
    private const TABLES = [
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
        'store_products',
        'category_products',
        'expenses',
        'expense_categories',
        'products',
        'categories',
        'paiement_journaliers',
        'journaliers',
        'logistics',
        'temoignages',
        'contact_messages',
        'otp_codes',
        'notifications',
        'customers',
        'stores',
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

        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        foreach (self::TABLES as $table) {
            $count = DB::table($table)->count();
            DB::table($table)->truncate();
            $this->info("{$table}: {$count} ligne(s) supprimée(s).");
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1');

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
