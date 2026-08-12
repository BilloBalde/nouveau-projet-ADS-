<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->onDelete('cascade');
            $table->string('type');
            $table->integer('amount');
            $table->integer('dispo_before')->default(0);
            $table->integer('dispo_after')->default(0);
            $table->string('reference');
            $table->timestamps();
            $table->unique('reference', 'stock_histories_reference_unique');
            $table->index(['store_id', 'type', 'reference'], 'stock_histories_store_id_type_reference_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_histories');
    }
};
