<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('module');
            $table->timestamps();
            $table->unique('slug', 'permissions_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};
