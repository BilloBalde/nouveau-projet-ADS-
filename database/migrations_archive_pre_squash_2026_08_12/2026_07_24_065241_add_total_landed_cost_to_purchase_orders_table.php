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
        
            Schema::table('purchase_orders', function (Blueprint $table) {
                $table->decimal('total_landed_cost_gnf', 18, 2)->nullable();
            });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
               $table->dropColumn('total_landed_cost_gnf');
        });
    }
};
