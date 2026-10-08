<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Propriétaire de la marchandise d'une ligne de devis (NULL = fonds propres),
 * repris sur la vente à la validation du devis.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devi_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('fund_id')->nullable()->after('store_id');
        });
    }

    public function down(): void
    {
        Schema::table('devi_lines', function (Blueprint $table) {
            $table->dropColumn('fund_id');
        });
    }
};
