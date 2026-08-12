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
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'promo_percent')) {
                $table->decimal('promo_percent', 5, 2)->nullable()->after('promo_price');
            }
            if (!Schema::hasColumn('products', 'promo_start_date')) {
                $table->date('promo_start_date')->nullable()->after('promo_percent');
            }
            if (!Schema::hasColumn('products', 'promo_end_date')) {
                $table->date('promo_end_date')->nullable()->after('promo_start_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['promo_percent', 'promo_start_date', 'promo_end_date']);
        });
    }
};
