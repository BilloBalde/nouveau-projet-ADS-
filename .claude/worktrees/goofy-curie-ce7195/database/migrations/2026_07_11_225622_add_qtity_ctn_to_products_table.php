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
        if (!Schema::hasColumn('products', 'qtityCtn')) {
            Schema::table('products', function (Blueprint $table) {
                $table->unsignedInteger('qtityCtn')->default(1)->after('pcs');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('products', 'qtityCtn')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('qtityCtn');
            });
        }
    }
};
