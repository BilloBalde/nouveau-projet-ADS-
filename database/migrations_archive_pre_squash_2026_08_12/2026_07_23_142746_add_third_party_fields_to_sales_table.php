<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->boolean('is_third_party')->default(false)->after('interet');
            $table->foreignId('partner_id')->nullable()->constrained('partners')->after('is_third_party');
            $table->decimal('purchase_price_from_partner', 15, 2)->nullable()->after('partner_id');
            // On n'a plus besoin de commission_rate
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            //
        });
    }
};
