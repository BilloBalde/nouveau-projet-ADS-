<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('purchase_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price_foreign', 14, 2)->nullable();
            $table->decimal('unit_price_gnf', 14, 2);
            $table->decimal('cbm_per_unit', 10, 4)->nullable();
            $table->decimal('line_total_cbm', 10, 3)->nullable();
            $table->decimal('allocated_freight_gnf', 14, 2)->default(0);
            $table->decimal('allocated_customs_gnf', 14, 2)->default(0);
            $table->decimal('landed_unit_cost_gnf', 14, 2);
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_order_items');
    }
};
