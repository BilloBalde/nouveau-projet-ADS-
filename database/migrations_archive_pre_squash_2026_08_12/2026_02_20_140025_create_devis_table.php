<?php
// database/migrations/xxxx_xx_xx_create_devis_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('devis', function (Blueprint $table) {
            $table->id();
            $table->string('numero_devis')->unique(); // Format: DEV-YYYYMM-XXXX
            $table->foreignId('store_id')->constrained('stores');
            $table->foreignId('customer_id')->nullable()->constrained('customers');
            $table->text('notes')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->enum('status', ['draft', 'sent', 'accepted', 'rejected', 'expired'])->default('draft');
            $table->date('valid_until')->nullable(); // Date d'expiration du devis
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('validated_by')->nullable()->constrained('users');
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('devis');
    }
};