<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Second numéro de l'entreprise, affiché à côté du principal sur la vitrine.
 * Il était écrit en dur dans les vues, donc impossible à corriger sans toucher
 * au code — comme l'étaient l'e-mail et le numéro principal avant eux.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('phone_secondary')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('phone_secondary');
        });
    }
};
