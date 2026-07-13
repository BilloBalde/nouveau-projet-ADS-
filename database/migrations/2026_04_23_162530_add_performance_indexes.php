<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->index('numeroFacture', 'sales_numerofacture_index');
            $table->index('product_id',    'sales_product_id_index');
            $table->index('store_id',      'sales_store_id_index');
        });

        Schema::table('factures', function (Blueprint $table) {
            $table->index('numero_facture', 'factures_numero_facture_index');
            $table->index('customer_id',    'factures_customer_id_index');
            $table->index('store_id',       'factures_store_id_index');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index('invoice_number', 'orders_invoice_number_index');
            $table->index('status',         'orders_status_index');
            $table->index('user_id',        'orders_user_id_index');
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->index('order_id',   'order_items_order_id_index');
            $table->index('product_id', 'order_items_product_id_index');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->index('product_id', 'purchases_product_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_numerofacture_index');
            $table->dropIndex('sales_product_id_index');
            $table->dropIndex('sales_store_id_index');
        });
        Schema::table('factures', function (Blueprint $table) {
            $table->dropIndex('factures_numero_facture_index');
            $table->dropIndex('factures_customer_id_index');
            $table->dropIndex('factures_store_id_index');
        });
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_invoice_number_index');
            $table->dropIndex('orders_status_index');
            $table->dropIndex('orders_user_id_index');
        });
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropIndex('order_items_order_id_index');
            $table->dropIndex('order_items_product_id_index');
        });
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex('purchases_product_id_index');
        });
    }
};
