<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Vérifier si la colonne existe avant de l'ajouter
            if (!Schema::hasColumn('customers', 'balance')) {
                $table->decimal('balance', 15, 2)->default(0.00);
            }
            if (!Schema::hasColumn('customers', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['balance', 'deleted_at']);
        });
    }
};