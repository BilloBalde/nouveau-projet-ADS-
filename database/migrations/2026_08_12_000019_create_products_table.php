<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('libelle');
            $table->string('sku');
            $table->string('image')->default('ib profile.jpg');
            $table->text('description');
            $table->double('price')->nullable();
            $table->boolean('is_promo')->default(0);
            $table->decimal('promo_price', 10, 2)->nullable();
            $table->decimal('promo_percent', 5, 2)->nullable();
            $table->date('promo_start_date')->nullable();
            $table->date('promo_end_date')->nullable();
            $table->decimal('rating', 3, 1)->default(4.5);
            $table->double('pcs')->nullable();
            $table->decimal('price_sale', 14, 2)->nullable()->default(0.00);
            $table->timestamps();
            $table->unique('sku', 'products_sku_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
