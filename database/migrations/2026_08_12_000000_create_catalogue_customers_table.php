<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('catalogue_customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('password');
            $table->timestamps();
            $table->unique('email', 'catalogue_customers_email_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('catalogue_customers');
    }
};
