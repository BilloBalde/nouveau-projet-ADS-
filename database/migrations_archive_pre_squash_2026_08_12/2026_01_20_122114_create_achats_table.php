<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('achats', function (Blueprint $table) {
            $table->id();
            $table->string("identifier")->unique();
            $table->foreignId('store_id')->constrained('stores')->onUpdate('cascade')->onDelete('restrict');
            $table->integer('total_ctns')->default(1);
            $table->integer('total_pcs')->default(1);
            $table->decimal('total_amount', 15,2)->nullable();
            $table->decimal('shippment', 15,2)->nullable();
            $table->decimal("grand_total", 15,2);
            $table->date("date_achat")->nullable();
            $table->enum('status', ['commanded','canceled','delivered','shipped','paid'])->default('commanded');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('achats');
    }
};
