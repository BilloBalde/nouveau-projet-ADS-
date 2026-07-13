<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('currency_settings', 'rate_to_gnf')) {
            Schema::table('currency_settings', function (Blueprint $table) {
                // Null pour GNF elle-même (taux implicite de 1, pas besoin de conversion).
                $table->decimal('rate_to_gnf', 14, 4)->nullable()->after('currencySymbol');
            });
        }

        // Seed idempotent : ne crée que ce qui manque, ne touche pas aux taux déjà saisis.
        foreach ([
            ['currencyName' => 'Franc Guinéen', 'currencyCode' => 'GNF', 'currencySymbol' => 'GNF', 'rate_to_gnf' => 1],
            ['currencyName' => 'Yuan Chinois', 'currencyCode' => 'CNY', 'currencySymbol' => '¥', 'rate_to_gnf' => null],
            ['currencyName' => 'Dollar Américain', 'currencyCode' => 'USD', 'currencySymbol' => '$', 'rate_to_gnf' => null],
        ] as $currency) {
            $exists = DB::table('currency_settings')->where('currencyCode', $currency['currencyCode'])->exists();
            if (!$exists) {
                DB::table('currency_settings')->insert(array_merge($currency, [
                    'status' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('currency_settings', 'rate_to_gnf')) {
            Schema::table('currency_settings', function (Blueprint $table) {
                $table->dropColumn('rate_to_gnf');
            });
        }
    }
};
