<?php

use App\Support\LocationPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fonds des associés.
 *
 * Un fonds = l'argent apporté par un associé et tout ce qu'il finance :
 * achats, marchandises, ventes, résultat. Les fonds propres du patron ne sont
 * pas un enregistrement : fund_id NULL = fonds propres. Ainsi tout le stock et
 * l'historique existants restent, sans conversion, aux fonds propres.
 *
 * - funds              : un associé, sa clé de partage (fixe) ;
 * - fund_transactions  : son compte à part (apports, remboursements de capital,
 *                        versements de bénéfice, prélèvements de la part patron) ;
 * - fund_id sur le stock, les mouvements, les achats, les ventes, les
 *   transferts et les articles de commande : la marchandise garde toujours
 *   son propriétaire, même dans un entrepôt partagé ;
 * - sales.unit_cost    : coût de revient retenu au moment de la vente.
 */
return new class extends Migration
{
    private array $withFund = ['store_products', 'stock_movements', 'sales', 'purchase_orders', 'stock_transfers', 'order_items', 'users'];

    public function up(): void
    {
        Schema::create('funds', function (Blueprint $table) {
            $table->id();
            $table->string('name');                       // ex. « Fonds Mamadou Diallo »
            $table->string('associate_name');
            $table->string('phone')->nullable();
            $table->decimal('owner_share', 5, 2)->default(20);      // part du patron, en %
            $table->decimal('associate_share', 5, 2)->default(80);  // part de l'associé, en %
            $table->boolean('active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('fund_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fund_id');
            // apport | remboursement_capital | versement_benefice | prelevement_patron
            $table->string('type', 30);
            $table->decimal('amount', 15, 2);
            $table->date('date');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->index(['fund_id', 'type'], 'fund_transactions_fund_type_index');
        });

        foreach ($this->withFund as $name) {
            Schema::table($name, function (Blueprint $table) use ($name) {
                $table->unsignedBigInteger('fund_id')->nullable();
                $table->index('fund_id', $name . '_fund_id_index');
                if ($name === 'sales') {
                    $table->decimal('unit_cost', 15, 2)->nullable();
                }
            });
        }

        LocationPermissions::install();
    }

    public function down(): void
    {
        foreach ($this->withFund as $name) {
            Schema::table($name, function (Blueprint $table) use ($name) {
                $table->dropIndex($name . '_fund_id_index');
                $table->dropColumn($name === 'sales' ? ['fund_id', 'unit_cost'] : ['fund_id']);
            });
        }

        Schema::dropIfExists('fund_transactions');
        Schema::dropIfExists('funds');
    }
};
