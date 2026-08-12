<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('mark');
            $table->string('customerName');
            $table->string('tel');
            $table->string('address');
            $table->double('total_taken')->default(0);
            $table->double('total_repaid')->default(0);
            $table->double('balance')->default(0);
            $table->string('email')->nullable();
            $table->tinyInteger('fidelite')->default(1);
            $table->timestamps();
            $table->softDeletes();
            $table->unique('mark', 'customers_mark_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
