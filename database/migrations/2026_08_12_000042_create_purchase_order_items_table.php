<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('cartons')->nullable();
            $table->unsignedInteger('pieces_per_carton')->nullable();
            $table->decimal('unit_price_foreign', 14, 2)->nullable();
            $table->decimal('unit_price_gnf', 14, 2);
            $table->decimal('cbm_per_unit', 10, 4)->nullable();
            $table->decimal('cbm_per_carton', 10, 4)->nullable();
            $table->decimal('line_total_cbm', 10, 3)->nullable();
            $table->decimal('allocated_freight_gnf', 14, 2)->default(0.00);
            $table->decimal('allocated_customs_gnf', 14, 2)->default(0.00);
            $table->decimal('allocated_other_fees_gnf', 14, 2)->default(0.00);
            $table->decimal('landed_unit_cost_gnf', 14, 2);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
