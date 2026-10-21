<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Retire products.pcs, second total de stock tenu en parallèle de
 * store_products.
 *
 * Le stock réel vit dans store_products, une ligne par emplacement (et par
 * propriétaire depuis les fonds d'associés). products.pcs recopiait un total
 * global, entretenu par StockService à chaque mouvement et réparé à la main
 * depuis la fiche produit — mais les achats ne l'alimentaient pas, et le code
 * lui-même documentait sa dérive : le tableau de bord recalculait le stock à
 * la volée pour ne pas s'y fier, StockService::totalAvailable() aussi.
 *
 * Au moment de ce retrait, la colonne n'avait plus un seul lecteur : elle
 * était écrite par trois chemins et lue par aucun. Entretenir un chiffre dont
 * personne ne se sert, et dont on sait qu'il est faux, coûte une écriture à
 * chaque mouvement de stock et un doute à chaque lecture.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('products', 'pcs')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('pcs');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('products', 'pcs')) {
            Schema::table('products', function (Blueprint $table) {
                $table->double('pcs')->nullable()->after('rating');
            });
        }
    }
};
