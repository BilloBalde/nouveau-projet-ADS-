<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PurchaseOrderCostingService::costOrder() écrit déjà 'cartons' et
     * 'pieces_per_carton' sur PurchaseOrderItem (create_purchase_order_items_table ne
     * les avait jamais créées) : toute création/modification d'achat plantait avec une
     * erreur SQL "Unknown column". On ajoute aussi cbm_per_carton (nouvelle saisie —
     * le CBM par unité est désormais calculé automatiquement en le divisant par le
     * nombre de pièces par carton) et allocated_other_fees_gnf (répartition des frais
     * divers, symétrique à allocated_freight_gnf / allocated_customs_gnf déjà présentes).
     */
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            if (! Schema::hasColumn('purchase_order_items', 'cartons')) {
                $table->unsignedInteger('cartons')->nullable()->after('quantity');
            }
            if (! Schema::hasColumn('purchase_order_items', 'pieces_per_carton')) {
                $table->unsignedInteger('pieces_per_carton')->nullable()->after('cartons');
            }
            if (! Schema::hasColumn('purchase_order_items', 'cbm_per_carton')) {
                $table->decimal('cbm_per_carton', 10, 4)->nullable()->after('cbm_per_unit');
            }
            if (! Schema::hasColumn('purchase_order_items', 'allocated_other_fees_gnf')) {
                $table->decimal('allocated_other_fees_gnf', 14, 2)->default(0)->after('allocated_customs_gnf');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn(['cartons', 'pieces_per_carton', 'cbm_per_carton', 'allocated_other_fees_gnf']);
        });
    }
};
