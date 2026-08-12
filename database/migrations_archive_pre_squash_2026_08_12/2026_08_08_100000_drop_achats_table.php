<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Le module Achat (Proforma/LigneCommande) est remplacé par PurchaseOrder /
     * PurchaseOrderItem (menu "Achat"). La table `achats` est vide (0 ligne) — aucune
     * donnée perdue. `ligne_commandes` est conservée (utilisée ailleurs comme filet de
     * secours prix via Product::latestLigneCommande), donc on retire seulement sa
     * contrainte de clé étrangère vers `achats` avant de supprimer cette dernière.
     */
    public function up(): void
    {
        if (Schema::hasTable('ligne_commandes') && Schema::hasColumn('ligne_commandes', 'achat_id')) {
            Schema::table('ligne_commandes', function (Blueprint $table) {
                $table->dropForeign(['achat_id']);
            });
        }

        Schema::dropIfExists('achats');
    }

    public function down(): void
    {
        Schema::create('achats', function (Blueprint $table) {
            $table->id();
            $table->string('identifier')->unique();
            $table->foreignId('store_id')->constrained('stores')->onUpdate('cascade')->onDelete('restrict');
            $table->integer('total_ctns')->default(1);
            $table->integer('total_pcs')->default(1);
            $table->integer('pieces_per_carton')->nullable();
            $table->decimal('total_amount', 15, 2)->nullable();
            $table->decimal('shippment', 15, 2)->nullable();
            $table->decimal('grand_total', 15, 2);
            $table->date('date_achat')->nullable();
            $table->enum('status', ['commanded', 'canceled', 'delivered', 'shipped', 'paid'])->default('commanded');
            $table->timestamps();
        });

        if (Schema::hasTable('ligne_commandes') && Schema::hasColumn('ligne_commandes', 'achat_id')) {
            Schema::table('ligne_commandes', function (Blueprint $table) {
                $table->foreign('achat_id')->references('id')->on('achats')->restrictOnDelete();
            });
        }
    }
};
