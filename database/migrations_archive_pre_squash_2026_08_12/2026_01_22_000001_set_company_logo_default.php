<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::table('companies')->update(['logo' => 'logo.png']);
    }

    public function down()
    {
        // No-op: keep current logo values
    }
};
