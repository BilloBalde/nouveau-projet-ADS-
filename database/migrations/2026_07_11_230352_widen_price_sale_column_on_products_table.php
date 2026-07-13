<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * price_sale was decimal(8,2) — caps at 999 999,99 GNF, too small for
     * real purchase prices (seen up to several millions). Raw SQL avoids
     * requiring doctrine/dbal just for a column type change.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE products MODIFY price_sale DECIMAL(14,2) NULL DEFAULT 0');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE products MODIFY price_sale DECIMAL(8,2) NULL DEFAULT 0');
    }
};
