<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Séances d'inventaire : le chiffre d'affaires déduit du stock.
 *
 * Certaines boutiques ne saisissent pas leurs ventes une par une. Elles
 * vendent, puis comptent le stock restant à intervalle régulier (un mois, par
 * exemple). Ce qui a disparu entre deux comptages, achats et transferts
 * déduits, a été vendu :
 *
 *   vendu = compté au début + achats + transferts entrants
 *           − transferts sortants − pertes déclarées − compté à la fin
 *
 *   chiffre d'affaires = vendu × prix de vente
 *
 * Deux choix structurent ces tables :
 *
 * 1. Tous les termes du calcul sont ÉCRITS dans la ligne au moment de la
 *    validation, pas recalculés à l'affichage. Un chiffre d'affaires de
 *    janvier ne doit pas changer parce qu'un prix a bougé en mars, et le
 *    gérant doit pouvoir refaire la soustraction à la main, terme par terme,
 *    sur le rapport qu'il a sous les yeux.
 *
 * 2. Les pertes déclarées (casse, vol, péremption) sortent du stock mais pas
 *    du chiffre d'affaires. Sans cette distinction, la formule facture les
 *    pertes au prix de vente : le chiffre d'affaires est gonflé et
 *    l'inventaire ne révèle plus rien.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('reference');
            $table->foreignId('store_id')->constrained('stores')->onDelete('restrict');

            // Borne de début : la séance précédente de CETTE boutique. Null pour
            // la première, qui ne fait alors que constater le stock de départ.
            $table->foreignId('previous_session_id')->nullable()->constrained('inventory_sessions')->nullOnDelete();

            $table->date('counted_on');
            $table->enum('status', ['draft', 'validated'])->default('draft');

            // Figés à la validation : un rapport déjà établi ne se recalcule pas.
            $table->decimal('total_revenue_gnf', 18, 2)->nullable();
            $table->decimal('total_loss_gnf', 18, 2)->nullable();
            $table->unsignedInteger('total_sold_quantity')->nullable();

            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();

            $table->unique('reference', 'inventory_sessions_reference_unique');
            $table->index(['store_id', 'counted_on'], 'inventory_sessions_store_date_index');
        });

        Schema::create('inventory_session_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_session_id')->constrained('inventory_sessions')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');

            // Propriétaire de la marchandise : null = fonds propres, sinon le
            // fonds d'un associé. Le stock est déjà séparé ainsi.
            $table->unsignedBigInteger('fund_id')->nullable();

            // Unité figée, comme sur les ventes et les mouvements de stock.
            $table->string('unite', 20)->nullable();

            $table->integer('counted_quantity');
            $table->integer('loss_quantity')->default(0);
            $table->string('loss_reason')->nullable();

            // Prix de vente figé : le chiffre d'affaires ne bougera plus.
            $table->decimal('unit_price_gnf', 14, 2)->default(0);

            // Termes du calcul, figés à la validation pour que le rapport soit
            // vérifiable à la main sans rejouer le journal.
            $table->integer('opening_quantity')->default(0);
            $table->integer('purchased_quantity')->default(0);
            $table->integer('transfer_in_quantity')->default(0);
            $table->integer('transfer_out_quantity')->default(0);

            // Tout autre mouvement tracé sur la période (ajustement manuel,
            // correction d'un achat, vente saisie malgré ce mode) : montré à
            // part, jamais fondu dans les ventes, sinon l'égalité ne se
            // vérifie plus et le gérant perd confiance dans le chiffre.
            $table->integer('other_quantity')->default(0);

            $table->integer('sold_quantity')->default(0);
            $table->decimal('revenue_gnf', 18, 2)->default(0);

            $table->timestamps();

            $table->unique(['inventory_session_id', 'product_id', 'fund_id'], 'inventory_session_lines_unique');
            $table->index('product_id', 'inventory_session_lines_product_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_session_lines');
        Schema::dropIfExists('inventory_sessions');
    }
};
