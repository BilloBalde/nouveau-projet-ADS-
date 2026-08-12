<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference');
            $table->foreignId('store_id')->constrained('stores')->onDelete('restrict');
            $table->enum('origin', ['chine','guinee']);
            $table->string('currency_code', 10)->nullable();
            $table->decimal('exchange_rate_used', 14, 4)->nullable();
            $table->decimal('transport_cost_gnf', 14, 2)->default(0.00);
            $table->decimal('customs_cost_gnf', 14, 2)->default(0.00);
            $table->decimal('other_fees_gnf', 14, 2)->default(0.00);
            $table->decimal('total_cbm', 10, 3)->nullable();
            $table->enum('status', ['pending','received','cancelled'])->default('pending');
            $table->date('date_emis');
            $table->date('date_recu')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('total_landed_cost_gnf', 18, 2)->nullable();
            $table->timestamps();
            $table->unique('reference', 'purchase_orders_reference_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
