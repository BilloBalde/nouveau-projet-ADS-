<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Une « boutique » (table stores) est désormais un emplacement de stock au sens
 * large : point de vente, entrepôt, ou stock interne rattaché à un magasin.
 *
 * - type      : magasin | entrepot | stock — les boutiques existantes restent
 *               des magasins, rien n'est deviné à partir de leur nom ;
 * - parent_id : emplacement de rattachement (Entrepôt 3 → Magasin principal,
 *               Stock A1 → Magasin Madina). Pas de contrainte de clé étrangère
 *               pour rester compatible avec SQLite (tests) ; l'intégrité est
 *               vérifiée par la validation ;
 * - is_main   : magasin principal / siège. C'est une donnée, jamais un
 *               `if ($storeId === 1)` dans le code.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->string('type', 20)->default('magasin')->after('store_name');
            $table->unsignedBigInteger('parent_id')->nullable()->after('type');
            $table->boolean('is_main')->default(false)->after('parent_id');

            $table->index('type', 'stores_type_index');
            $table->index('parent_id', 'stores_parent_id_index');
        });

        Schema::table('store_products', function (Blueprint $table) {
            $table->index(['store_id', 'product_id'], 'store_products_store_product_index');
        });
    }

    public function down(): void
    {
        // Voir la migration 000004 : index simple pour la clé étrangère avant suppression.
        Schema::table('store_products', function (Blueprint $table) {
            $table->index('store_id', 'store_products_store_id_fk_index');
        });
        Schema::table('store_products', function (Blueprint $table) {
            $table->dropIndex('store_products_store_product_index');
        });

        Schema::table('stores', function (Blueprint $table) {
            $table->dropIndex('stores_type_index');
            $table->dropIndex('stores_parent_id_index');
            $table->dropColumn(['type', 'parent_id', 'is_main']);
        });
    }
};
