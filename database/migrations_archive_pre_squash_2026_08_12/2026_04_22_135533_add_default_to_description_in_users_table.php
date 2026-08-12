<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Superseded by 2026_04_21_133615_make_description_nullable_in_users_table,
     * which matches the live column exactly (TEXT NULL, no default). This
     * migration previously used Schema::change() without doctrine/dbal
     * installed (would fatal error) and set a default('') that doesn't
     * match production (default is NULL there). Kept as a guarded no-op to
     * preserve migration history.
     */
    public function up(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('users')) {
            return;
        }
    }
};
