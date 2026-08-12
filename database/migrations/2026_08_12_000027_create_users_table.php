<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username');
            $table->string('email');
            $table->string('password')->nullable();
            $table->string('motdepasse')->nullable();
            $table->string('status');
            $table->string('token');
            $table->string('profilePic')->nullable();
            $table->string('phone');
            $table->foreignId('role_id')->constrained('roles')->onDelete('restrict');
            $table->unsignedBigInteger('store_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique('email', 'users_email_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
