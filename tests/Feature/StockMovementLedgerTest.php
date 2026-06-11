<?php

namespace Tests\Feature;

use App\Models\PriceType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockMovementLedgerTest extends TestCase
{
    use RefreshDatabase;

    public function test_sale_writes_stock_movement_ledger_row(): void
    {
        PriceType::create(['code' => 'umum', 'name' => 'Umum', 'sort_order' => 1]);
        $kasir = User::factory()->create();
        $product = Product::create(['name' => 'P', 'stock' => 10, 'min_stock' => 1, 'purchase_price' => 1000, 'is_active' => true]);
        $product->prices()->create(['price_type' => 'umum', 'price' => 5000, 'is_active_default' => true]);

        $trx = app(TransactionService::class)->create([
            'payment_amount' => 50000,
            'items' => [['item_type' => 'product', 'product_id' => $product->id, 'price_type' => 'umum', 'qty' => 3]],
        ], $kasir);

        $this->assertDatabaseCount('stock_movements', 1);
        $movement = StockMovement::first();
        $this->assertEquals('sale', $movement->type->value);
        $this->assertEquals(-3, $movement->qty_change);
        $this->assertEquals(10, $movement->stock_before);
        $this->assertEquals(7, $movement->stock_after);
        $this->assertEquals('transaction', $movement->reference_type);
        $this->assertEquals($trx->id, $movement->reference_id);
        $this->assertEquals($kasir->id, $movement->user_id);
    }
}
