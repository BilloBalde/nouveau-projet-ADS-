<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Superseded: invoice_number is now created directly by
     * 2026_04_18_165500_create_orders_table (orders had no migration at
     * all before, so its full live schema — including invoice_number —
     * is now defined there). Kept as a guarded no-op to preserve migration
     * history.
     */
    public function up(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('orders')) {
            return;
        }
    }
};
