<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('delivery_address_id')->constrained('delivery_addresses')->onDelete('cascade');
            $table->decimal('total_amount', 12, 2);
            $table->string('status')->default('pending');
            $table->string('payment_method')->default('cod');
            $table->string('payment_status')->default('pending');
            $table->string('transaction_id')->nullable();
            $table->string('invoice_number')->nullable();
            $table->timestamps();
            $table->index('invoice_number', 'orders_invoice_number_index');
            $table->index('status', 'orders_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
