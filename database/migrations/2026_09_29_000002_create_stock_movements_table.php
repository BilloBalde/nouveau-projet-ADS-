<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Journal des mouvements de stock. Chaque variation de store_products passe
 * par StockService, qui écrit ici une ligne : quel produit, quel emplacement,
 * combien, pourquoi, qui, quand, et le document métier à l'origine (vente,
 * achat, transfert, commande, inventaire…).
 *
 * Le journal est en ajout seul : le modèle StockMovement refuse la
 * modification et la suppression. Une erreur se corrige par un nouveau
 * mouvement de type CORRECTION.
 *
 * Pas de clé étrangère sur product_id / store_id : l'historique doit survivre
 * à la suppression d'un produit ou d'une boutique.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('store_id');
            $table->string('type', 30);
            // Signée : positive pour une entrée, négative pour une sortie.
            $table->integer('quantity');
            $table->integer('stock_before')->nullable();
            $table->integer('stock_after')->nullable();
            // Autre extrémité d'un transfert (source pour une entrée, destination pour une sortie).
            $table->unsignedBigInteger('counterpart_store_id')->nullable();
            // Document métier : classe courte (sale, purchase_order, stock_transfer…) + id.
            $table->string('source_type', 40)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            // Référence lisible : n° de facture, référence d'achat, de transfert…
            $table->string('document_ref')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->decimal('unit_price', 15, 2)->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'created_at'], 'stock_movements_product_date_index');
            $table->index(['store_id', 'created_at'], 'stock_movements_store_date_index');
            $table->index(['source_type', 'source_id'], 'stock_movements_source_index');
            $table->index('document_ref', 'stock_movements_document_ref_index');
            $table->index('type', 'stock_movements_type_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
