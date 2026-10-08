<?php

use App\Support\LocationPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Commandes en ligne : traitées au siège par défaut (store_id vide = non
 * attribuée, visible seulement des profils à vue globale). Le propriétaire
 * peut en attribuer une à une boutique : seule cette boutique la voit, la
 * prépare et la livre.
 *
 * Les commandes existantes restent non attribuées : rien n'est deviné.
 * Ajoute aussi la permission orders.assign (via LocationPermissions).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('store_id')->nullable()->after('delivery_address_id');
            $table->unsignedBigInteger('assigned_by')->nullable()->after('store_id');
            $table->timestamp('assigned_at')->nullable()->after('assigned_by');
            $table->index('store_id', 'orders_store_id_index');
        });

        LocationPermissions::install();
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_store_id_index');
            $table->dropColumn(['store_id', 'assigned_by', 'assigned_at']);
        });
    }
};
