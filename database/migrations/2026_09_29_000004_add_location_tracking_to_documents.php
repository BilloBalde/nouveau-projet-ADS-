<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Emplacement source au niveau de chaque ligne, vendeur et canal sur la facture,
 * fournisseur sur l'achat.
 *
 * Les ventes (table sales) portaient déjà leur store_id ligne par ligne : c'est
 * l'emplacement réellement débité. Elles n'ont donc besoin d'aucune migration
 * de données — l'historique est déjà exact.
 *
 * Les reprises de données ci-dessous ne s'appuient que sur des liens fiables :
 *  - factures.user_id  ← sales.user_id des lignes de la facture (même auteur) ;
 *  - factures.channel  ← 'ecommerce' pour les factures INV-… (seul préfixe
 *                         généré par l'approbation des commandes). Les autres
 *                         restent NULL = « canal inconnu », rien n'est deviné ;
 *  - order_items.store_id ← store_id de la ligne de vente créée à l'approbation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('factures', function (Blueprint $table) {
            if (! Schema::hasColumn('factures', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('store_id');
            }
            $table->string('channel', 20)->nullable()->after('store_id');
            $table->index('channel', 'factures_channel_index');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->unsignedBigInteger('store_id')->nullable()->after('product_id');
        });

        Schema::table('devi_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('store_id')->nullable()->after('product_id');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->unsignedBigInteger('partner_id')->nullable()->after('store_id');
            $table->unsignedBigInteger('created_by')->nullable()->after('partner_id');
            $table->index('partner_id', 'purchase_orders_partner_id_index');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->index(['store_id', 'created_at'], 'sales_store_date_index');
            $table->index(['user_id', 'created_at'], 'sales_user_date_index');
        });

        Schema::create('store_assignments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('assigned_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'ended_at'], 'store_assignments_user_open_index');
            $table->index('store_id', 'store_assignments_store_index');
        });

        // --- Reprise des données ---

        DB::statement('UPDATE factures SET user_id = (
            SELECT s.user_id FROM sales s
            WHERE s.numeroFacture = factures.numero_facture AND s.user_id IS NOT NULL
            ORDER BY s.id LIMIT 1
        ) WHERE user_id IS NULL');

        DB::table('factures')->where('numero_facture', 'like', 'INV-%')->update(['channel' => 'ecommerce']);

        DB::statement('UPDATE order_items SET store_id = (
            SELECT s.store_id FROM sales s
            INNER JOIN orders o ON o.invoice_number = s.numeroFacture
            WHERE o.id = order_items.order_id AND s.product_id = order_items.product_id
            ORDER BY s.id LIMIT 1
        ) WHERE store_id IS NULL');

        // Vendeurs : l'affectation vivait sur stores.user_id (une seule personne
        // par boutique). Elle est recopiée sur users.store_id, qui devient la
        // référence et autorise plusieurs vendeurs par boutique.
        $vendeurs = DB::table('users')->where('role_id', 3)->get(['id', 'store_id']);
        foreach ($vendeurs as $vendeur) {
            $storeId = $vendeur->store_id
                ?? DB::table('stores')->where('user_id', $vendeur->id)->orderBy('id')->value('id');

            if (! $storeId) {
                continue;
            }

            DB::table('users')->where('id', $vendeur->id)->update(['store_id' => $storeId]);
            DB::table('store_assignments')->insert([
                'user_id'    => $vendeur->id,
                'store_id'   => $storeId,
                'started_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('store_assignments');

        // MySQL a pu rattacher les clés étrangères store_id / user_id à ces index
        // composites : on leur redonne un index simple avant de les supprimer.
        Schema::table('sales', function (Blueprint $table) {
            $table->index('store_id', 'sales_store_id_fk_index');
            $table->index('user_id', 'sales_user_id_fk_index');
        });
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_store_date_index');
            $table->dropIndex('sales_user_date_index');
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropIndex('purchase_orders_partner_id_index');
            $table->dropColumn(['partner_id', 'created_by']);
        });

        Schema::table('devi_lines', function (Blueprint $table) {
            $table->dropColumn('store_id');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('store_id');
        });

        Schema::table('factures', function (Blueprint $table) {
            $table->dropIndex('factures_channel_index');
            $table->dropColumn(['channel', 'user_id']);
        });
    }
};
