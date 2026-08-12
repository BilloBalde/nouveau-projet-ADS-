<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('sales', 'user_id')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('store_id')->constrained('users')->nullOnDelete();
            });
        }
    }

    public function down()
    {
        if (Schema::hasColumn('sales', 'user_id')) {
            Schema::table('sales', function (Blueprint $table) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            });
        }
    }
};
