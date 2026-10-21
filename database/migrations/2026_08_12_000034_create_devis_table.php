<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devis', function (Blueprint $table) {
            $table->id();
            $table->string('numero_devis');
            $table->foreignId('store_id')->constrained('stores');
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->text('notes')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0.00);
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'expired'])->default('draft');
            $table->date('valid_until')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('validated_by')->nullable()->constrained('users');
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
            $table->unique('numero_devis', 'devis_numero_devis_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devis');
    }
};
