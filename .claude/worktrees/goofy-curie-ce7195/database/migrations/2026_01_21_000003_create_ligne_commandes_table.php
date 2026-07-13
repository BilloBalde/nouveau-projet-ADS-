<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
          Schema::create('ligne_commandes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('achat_id')->constrained('achats')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->integer('cartons')->default(0); // Number of cartons
            $table->integer('quantity')->default(0); // Quantity of the product
            $table->decimal('unit_price_purchase', 10, 2)->default(0.00); // Unit price of the product at purchase
            $table->decimal('total_price_purchase', 10, 2)->default(0.00); // Total price of the product at purchase
            $table->decimal('unit_price_sale', 10, 2)->default(0.00); // Unit price of the product at sale
            $table->decimal('montant_sale', 10, 2)->default(0.00); // Total price of the product at sale
            $table->decimal('ctn_price_sale', 10, 2)->default(0.00); // Total price of the product at sale per carton
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ligne_commandes');
    }
};
