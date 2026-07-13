<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Superseded / abandoned: promo_price was already added by
     * 2026_04_18_144812_add_best_promo_fields_to_products_table (conflicts
     * here). The remaining columns (views, is_featured, in_stock, size,
     * height, width, length) are unused anywhere in the codebase — the
     * dimension fields are English duplicates of the French taille/
     * hauteur/largeur/epaisseur that are actually used. Never applied to
     * the live database. Kept as a guarded no-op to preserve migration
     * history.
     */
    public function up(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }
    }
};
