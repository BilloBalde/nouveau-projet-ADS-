<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Les fonds sont communs : tout l'argent et toute la marchandise
 * appartiennent à la boutique.
 *
 * Le stock était tenu par propriétaire (fonds propres, ou fonds d'un
 * associé), avec une ligne par emplacement, produit et fonds. Les lignes sont
 * fusionnées — les quantités s'additionnent, rien n'est perdu — puis la
 * colonne fund_id disparaît de partout, avec les tables des fonds.
 *
 * Une ligne de stock par emplacement et par produit est désormais garantie
 * par la base : deux écritures concurrentes ne peuvent plus en créer deux.
 *
 * Le compte « Associé » ne servait qu'à consulter le relevé de son fonds :
 * il n'a plus d'objet. Ses titulaires sont désactivés plutôt que supprimés,
 * leur historique (ventes, mouvements) les cite encore.
 *
 * Irréversible : la répartition du stock entre fonds n'est pas conservée.
 */
return new class extends Migration
{
    private const AVEC_FONDS = ['store_products', 'stock_movements', 'sales', 'purchase_orders', 'stock_transfers', 'order_items', 'users', 'devi_lines'];

    /** Quantités et montants d'une ligne de comptage, qui s'additionnent à la fusion. */
    private const CUMULS_COMPTAGE = [
        'counted_quantity', 'loss_quantity', 'opening_quantity', 'purchased_quantity', 'transfer_in_quantity',
        'transfer_out_quantity', 'other_quantity', 'sold_quantity', 'revenue_gnf', 'margin_gnf',
    ];

    public function up(): void
    {
        DB::transaction(function () {
            $this->fusionnerLeStock();
            $this->fusionnerLesComptages();
            $this->retirerLeCompteAssocie();
        });

        // Chaque étape vérifie l'état avant d'agir : MySQL n'annule pas un
        // changement de structure, et une migration interrompue doit pouvoir
        // être relancée.
        //
        // Un index unique qui sert aussi une clé étrangère (la séance, le
        // magasin) ne peut être supprimé tant qu'un autre index ne commence
        // pas par la même colonne : le nouveau est toujours posé avant.
        $this->remplacerIndex('inventory_session_lines', 'inventory_session_lines_unique', ['inventory_session_id', 'product_id'], 'inventory_session_lines_product_unique');
        $this->remplacerIndex('store_products', 'store_products_store_product_index', ['store_id', 'product_id'], 'store_products_store_product_unique');

        foreach ([...self::AVEC_FONDS, 'inventory_session_lines'] as $nom) {
            if (! Schema::hasColumn($nom, 'fund_id')) {
                continue;
            }
            if (Schema::hasIndex($nom, $nom.'_fund_id_index')) {
                Schema::table($nom, fn (Blueprint $table) => $table->dropIndex($nom.'_fund_id_index'));
            }
            Schema::table($nom, fn (Blueprint $table) => $table->dropColumn('fund_id'));
        }

        Schema::dropIfExists('fund_transactions');
        Schema::dropIfExists('funds');
    }

    private function remplacerIndex(string $nom, string $ancien, array $colonnes, string $nouveau): void
    {
        if (! Schema::hasIndex($nom, $nouveau)) {
            Schema::table($nom, fn (Blueprint $table) => $table->unique($colonnes, $nouveau));
        }
        if (Schema::hasIndex($nom, $ancien)) {
            Schema::table($nom, fn (Blueprint $table) => $table->dropIndex($ancien));
        }
    }

    /** Une seule ligne par emplacement et produit : la plus ancienne garde le total. */
    private function fusionnerLeStock(): void
    {
        $doublons = DB::table('store_products')
            ->selectRaw('store_id, product_id, MIN(id) as garde, SUM(quantity) as quantite, SUM(COALESCE(ctns, 0)) as cartons, COUNT(*) as n')
            ->groupBy('store_id', 'product_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($doublons as $d) {
            DB::table('store_products')->where('id', $d->garde)->update(['quantity' => $d->quantite, 'ctns' => $d->cartons]);
            DB::table('store_products')
                ->where('store_id', $d->store_id)
                ->where('product_id', $d->product_id)
                ->where('id', '!=', $d->garde)
                ->delete();
        }
    }

    /**
     * Un comptage pouvait compter le même produit deux fois, une par fonds.
     * La ligne des fonds propres reste (son prix figé fait foi), les autres
     * s'y ajoutent.
     */
    private function fusionnerLesComptages(): void
    {
        $doublons = DB::table('inventory_session_lines')
            ->select('inventory_session_id', 'product_id')
            ->groupBy('inventory_session_id', 'product_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($doublons as $d) {
            $lignes = DB::table('inventory_session_lines')
                ->where('inventory_session_id', $d->inventory_session_id)
                ->where('product_id', $d->product_id)
                ->orderByRaw('CASE WHEN fund_id IS NULL THEN 0 ELSE 1 END')
                ->orderBy('id')
                ->get();

            $garde = $lignes->first();
            $totaux = [];
            foreach (self::CUMULS_COMPTAGE as $colonne) {
                $totaux[$colonne] = $lignes->sum($colonne);
            }

            DB::table('inventory_session_lines')->where('id', $garde->id)->update($totaux);
            DB::table('inventory_session_lines')->whereIn('id', $lignes->skip(1)->pluck('id'))->delete();
        }
    }

    private function retirerLeCompteAssocie(): void
    {
        $associe = DB::table('roles')->where('slug', 'associe')->value('id');
        $permission = DB::table('permissions')->where('slug', 'associates.self')->value('id');

        if ($associe) {
            $vendeur = DB::table('roles')->where('slug', 'user')->value('id');
            DB::table('users')->where('role_id', $associe)->update(['status' => 'Inactive', 'role_id' => $vendeur]);
            DB::table('role_permissions')->where('role_id', $associe)->delete();
            DB::table('roles')->where('id', $associe)->delete();
        }

        if ($permission) {
            DB::table('role_permissions')->where('permission_id', $permission)->delete();
            DB::table('user_permissions')->where('permission_id', $permission)->delete();
            DB::table('permissions')->where('id', $permission)->delete();
        }
    }

    public function down(): void
    {
        // La répartition du stock entre fonds n'est pas conservée.
    }
};
