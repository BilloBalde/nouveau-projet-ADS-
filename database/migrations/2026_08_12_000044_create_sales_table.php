<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->string('numeroFacture');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict')->onUpdate('cascade');
            $table->foreignId('store_id')->constrained('stores')->onDelete('restrict');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('quantity');
            $table->double('prix');
            $table->double('prixTotal');
            $table->double('interet');
            $table->boolean('is_third_party')->default(0);
            $table->foreignId('partner_id')->nullable()->constrained('partners');
            $table->decimal('purchase_price_from_partner', 15, 2)->nullable();
            $table->timestamps();
            $table->index('numeroFacture', 'sales_numerofacture_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
