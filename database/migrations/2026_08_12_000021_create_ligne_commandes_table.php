<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ligne_commandes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('achat_id');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->integer('cartons')->default(0);
            $table->integer('quantity')->default(0);
            $table->decimal('unit_price_purchase', 10, 2)->default(0.00);
            $table->decimal('total_price_purchase', 10, 2)->default(0.00);
            $table->decimal('unit_price_sale', 10, 2)->default(0.00);
            $table->decimal('montant_sale', 10, 2)->default(0.00);
            $table->decimal('ctn_price_sale', 10, 2)->default(0.00);
            $table->timestamps();
            $table->index('achat_id', 'ligne_commandes_achat_id_foreign');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ligne_commandes');
    }
};
