<?php

namespace Tests\Unit;

use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class StockServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['database.default' => 'sqlite']);
        config(['database.connections.sqlite' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);

        DB::purge('sqlite');

        Schema::create('products', function ($table) {
            $table->id();
            $table->integer('pcs')->default(0);
            $table->timestamps();
        });

        Schema::create('store_products', function ($table) {
            $table->id();
            $table->unsignedBigInteger('store_id');
            $table->unsignedBigInteger('product_id');
            $table->integer('quantity')->default(0);
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('store_products');
        Schema::dropIfExists('products');

        parent::tearDown();
    }

    public function test_decrement_if_available_updates_store_and_global_stock(): void
    {
        DB::table('products')->insert(['id' => 1, 'pcs' => 10]);
        DB::table('store_products')->insert([
            'id' => 1,
            'store_id' => 7,
            'product_id' => 1,
            'quantity' => 10,
        ]);

        $service = new StockService();

        $this->assertTrue($service->decrementIfAvailable(7, 1, 3));
        $this->assertSame(7, DB::table('store_products')->where('product_id', 1)->where('store_id', 7)->value('quantity'));
        $this->assertSame(7, DB::table('products')->where('id', 1)->value('pcs'));
    }
}
