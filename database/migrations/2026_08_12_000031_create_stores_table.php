<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('store_name');
            $table->foreignId('place_id')->constrained('places')->onDelete('restrict');
            $table->foreignId('user_id')->constrained('users')->onDelete('restrict');
            $table->string('store_picture');
            $table->tinyInteger('status')->default(1);
            $table->text('description');
            $table->string('address', 255)->nullable();
            $table->string('phone', 255)->nullable();
            $table->decimal('balance', 10, 2)->nullable()->default(0.00);
            $table->timestamps();
            $table->unique('store_name', 'stores_store_name_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
