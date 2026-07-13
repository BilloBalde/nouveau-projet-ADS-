<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * This migration is a no-op on the live database (its original content,
     * whatever it was intended to do with tenants on categories, was never
     * implemented). Kept as a guarded no-op to preserve migration history.
     */
    public function up()
    {
        if (!Schema::hasTable('categories')) {
            return;
        }
    }

    public function down()
    {
        if (!Schema::hasTable('categories')) {
            return;
        }
    }
};
