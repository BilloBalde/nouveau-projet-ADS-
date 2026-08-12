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
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();
            $table->foreignId('store_id')->constrained('stores')->restrictOnDelete();
            $table->enum('origin', ['chine', 'guinee']);
            $table->string('currency_code', 10)->nullable();
            $table->decimal('exchange_rate_used', 14, 4)->nullable();
            $table->decimal('transport_cost_gnf', 14, 2)->default(0);
            $table->decimal('customs_cost_gnf', 14, 2)->default(0);
            $table->decimal('other_fees_gnf', 14, 2)->default(0);
            $table->decimal('total_cbm', 10, 3)->nullable();
            $table->enum('status', ['pending', 'received', 'cancelled'])->default('pending');
            $table->date('date_emis');
            $table->date('date_recu')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
