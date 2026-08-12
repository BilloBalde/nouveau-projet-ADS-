<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Superseded: rating was already added by
     * 2026_04_18_144812_add_best_promo_fields_to_products_table as
     * decimal(3,1) default 4.5, which matches the live database. This
     * migration tried to add it again with a conflicting definition
     * (decimal(3,2) default 0) and never actually ran against production.
     * Kept as a guarded no-op to preserve migration history.
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
