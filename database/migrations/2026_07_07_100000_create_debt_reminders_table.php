<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debt_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('dette_id')->nullable()->constrained('dettes')->cascadeOnDelete();
            $table->foreignId('facture_id')->nullable()->constrained('factures')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->integer('reminder_count')->default(1);
            $table->timestamp('last_sent_at')->nullable();
            $table->enum('status', ['pending', 'sent', 'resolved'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debt_reminders');
    }
};
