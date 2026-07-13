<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * status was part of the original create_stores_table migration but
     * never made it to the live database (it's used by the store
     * active/inactive toggle in resources/views/stores/edit.blade.php and
     * index.blade.php, and its absence was causing every store edit to fail).
     */
    public function up(): void
    {
        if (!Schema::hasColumn('stores', 'status')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->tinyInteger('status')->default(1)->after('store_picture');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('stores', 'status')) {
            Schema::table('stores', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
