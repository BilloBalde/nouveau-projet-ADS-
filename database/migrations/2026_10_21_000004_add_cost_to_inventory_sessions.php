<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fige le prix de revient au comptage, pour pouvoir afficher une marge.
 *
 * La ligne d'inventaire ne retenait que le prix de vente : on savait ce qui
 * avait été encaissé, pas ce que la marchandise avait coûté. Le tableau de
 * bord d'une boutique qui ne saisit pas ses ventes ne pouvait donc annoncer
 * aucune marge.
 *
 * Le coût est figé comme le prix, et pour la même raison : un achat postérieur
 * change le prix de revient du produit, et la marge d'une période déjà close
 * ne doit pas bouger derrière.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_session_lines', function (Blueprint $table) {
            $table->decimal('unit_cost_gnf', 14, 2)->default(0)->after('unit_price_gnf');
            $table->decimal('margin_gnf', 18, 2)->default(0)->after('revenue_gnf');
        });

        Schema::table('inventory_sessions', function (Blueprint $table) {
            $table->decimal('total_margin_gnf', 18, 2)->nullable()->after('total_revenue_gnf');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_session_lines', function (Blueprint $table) {
            $table->dropColumn(['unit_cost_gnf', 'margin_gnf']);
        });

        Schema::table('inventory_sessions', function (Blueprint $table) {
            $table->dropColumn('total_margin_gnf');
        });
    }
};
