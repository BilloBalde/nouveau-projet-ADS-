<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Unités de vente : un produit se vend à la pièce, à la douzaine, au carton,
 * au paquet ou au rouleau. Le stock reste compté dans l'unité de base
 * (la pièce, ou le rouleau pour un produit vendu au rouleau) : une vente de
 * 2 cartons de 24 sort 48 pièces du stock.
 *
 * Sur la ligne de vente, `quantity` et `prix` restent en unité de base (les
 * rapports, marges et mouvements de stock ne changent pas) ; l'unité choisie,
 * la quantité saisie et le prix par unité sont conservés à côté pour la
 * facture et le reçu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('unites_vente')->nullable()->after('pcs');
            $table->unsignedInteger('pcs_par_carton')->nullable()->after('unites_vente');
            $table->unsignedInteger('pcs_par_paquet')->nullable()->after('pcs_par_carton');
            $table->string('dimension_rouleau', 60)->nullable()->after('pcs_par_paquet');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->string('unite', 20)->nullable()->after('quantity');
            $table->unsignedInteger('quantite_unite')->nullable()->after('unite');
            $table->unsignedInteger('facteur_unite')->nullable()->after('quantite_unite');
            $table->double('prix_unite')->nullable()->after('facteur_unite');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['unite', 'quantite_unite', 'facteur_unite', 'prix_unite']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['unites_vente', 'pcs_par_carton', 'pcs_par_paquet', 'dimension_rouleau']);
        });
    }
};
