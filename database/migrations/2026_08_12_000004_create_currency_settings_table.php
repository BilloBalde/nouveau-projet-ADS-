<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('currency_settings', function (Blueprint $table) {
            $table->id();
            $table->string('currencyName');
            $table->string('currencyCode');
            $table->string('currencySymbol');
            $table->decimal('rate_to_gnf', 14, 4)->nullable();
            $table->integer('status');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('currency_settings');
    }
};
