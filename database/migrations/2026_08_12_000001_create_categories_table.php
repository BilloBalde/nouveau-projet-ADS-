<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug');
            $table->string('category_type');
            $table->text('description');
            $table->string('image')->nullable();
            $table->timestamps();
            $table->unique('slug', 'categories_slug_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
