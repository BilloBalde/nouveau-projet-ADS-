<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Marque les produits dont le prix de vente a été fixé à la main.
 *
 * Chaque achat recalculait le prix de vente à « prix de revient + marge »,
 * sans exception : un prix ajusté par le gérant sur la fiche produit était
 * donc remis à la valeur automatique au prochain réapprovisionnement, sans
 * avertissement. Avec ce drapeau, un achat met toujours à jour le prix de
 * revient — c'est un fait comptable — mais ne touche plus au prix de vente
 * d'un produit dont le prix a été décidé.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'price_manuel')) {
            Schema::table('products', function (Blueprint $table) {
                $table->boolean('price_manuel')->default(false)->after('price_sale_minimum');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'price_manuel')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('price_manuel');
            });
        }
    }
};
