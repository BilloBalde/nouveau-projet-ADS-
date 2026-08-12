<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Ajouter 'partiellement livré' à l'enum livraison de factures
        // (la valeur est utilisée dans OrderManagementController::partialDeliver mais n'existait pas dans l'enum)
        DB::statement("ALTER TABLE factures MODIFY COLUMN livraison ENUM('non livré', 'partiellement livré', 'livré') NOT NULL DEFAULT 'non livré'");
    }

    public function down(): void
    {
        // Remettre l'enum original sans 'partiellement livré'
        // Les lignes avec 'partiellement livré' seront converties en 'non livré'
        DB::statement("UPDATE factures SET livraison = 'non livré' WHERE livraison = 'partiellement livré'");
        DB::statement("ALTER TABLE factures MODIFY COLUMN livraison ENUM('non livré', 'livré') NOT NULL DEFAULT 'non livré'");
    }
};
