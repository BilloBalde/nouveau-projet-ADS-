<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Superseded: invoice_number now lives on the orders table from creation
     * (see 2026_04_18_165500_create_orders_table). store_id was never used
     * anywhere in the codebase (Order records are never queried or written
     * with a store_id) and was never applied to the live database, so it's
     * intentionally left out to keep migrations matching production. Kept
     * as a guarded no-op to preserve migration history.
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
