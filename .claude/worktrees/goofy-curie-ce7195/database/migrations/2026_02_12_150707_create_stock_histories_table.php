<?php
// database/migrations/xxxx_xx_xx_xxxxxx_create_stock_histories_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('stock_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->onDelete('cascade');
            $table->string('type');               // sale, expense, purchase, ...
            $table->integer('amount');            // montant signé (+ entrée, – sortie)
            $table->integer('dispo_before')->default(0);
            $table->integer('dispo_after')->default(0);
            $table->string('reference')->unique(); // ex: sale_12, expense_5, purchase_3
            $table->timestamps();

            $table->index(['store_id', 'type', 'reference']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_histories');
    }
};