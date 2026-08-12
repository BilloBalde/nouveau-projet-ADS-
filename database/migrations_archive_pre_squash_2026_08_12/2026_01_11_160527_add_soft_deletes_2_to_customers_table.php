<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * No-op: deleted_at was already added to customers by
     * 2026_01_11_155943_add_soft_deletes_to_customers_table just moments
     * earlier. Kept as a guarded no-op to preserve migration history.
     */
    public function up()
    {
        if (!Schema::hasTable('customers')) {
            return;
        }
    }

    public function down()
    {
        if (!Schema::hasTable('customers')) {
            return;
        }
    }
};
