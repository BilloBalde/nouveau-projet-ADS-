<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_factures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->onDelete('restrict')->onUpdate('cascade');
            $table->string('receipt_number')->nullable();
            $table->string('paid_by')->nullable();
            $table->double('versement');
            $table->double('balance');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique('receipt_number', 'transaction_factures_receipt_number_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_factures');
    }
};
