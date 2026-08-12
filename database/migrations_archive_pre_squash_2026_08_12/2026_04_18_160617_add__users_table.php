<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE users MODIFY password VARCHAR(191) NULL');
        DB::statement('ALTER TABLE users MODIFY motdepasse VARCHAR(191) NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE users MODIFY password VARCHAR(191) NOT NULL');
        DB::statement('ALTER TABLE users MODIFY motdepasse VARCHAR(191) NOT NULL');
    }
};
