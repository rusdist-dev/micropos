<?php

namespace Tests\Feature;

use App\Models\PriceType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Transaction;
use App\Models\User;
use App\Services\ReturnService;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ReturnServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReturnService $service;
    private User $kasir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ReturnService::class);
        $this->kasir = User::factory()->create();
        PriceType::create(['code' => 'umum', 'name' => 'Umum', 'sort_order' => 1]);
    }

    private function product(int $stock, float $price): Product
    {
        $p = Product::create(['name' => 'P' . uniqid(), 'stock' => $stock, 'min_stock' => 1, 'purchase_price' => 0, 'is_active' => true]);
        $p->prices()->create(['price_type' => 'umum', 'price' => $price, 'is_active_default' => true]);

        return $p;
    }

    private function sell(Product $p, int $qty): Transaction
    {
        return app(TransactionService::class)->create([
            'payment_amount' => 99999999,
            'items' => [['item_type' => 'product', 'product_id' => $p->id, 'price_type' => 'umum', 'qty' => $qty]],
        ], $this->kasir);
    }

    public function test_return_with_restock_increments_stock(): void
    {
        $p = $this->product(10, 5000);
        $trx = $this->sell($p, 4);          // stok 10 -> 6
        $ti = $trx->items->first();

        $ret = $this->service->create([
            'transaction_id' => $trx->id,
            'returned_items' => [['transaction_item_id' => $ti->id, 'qty' => 2, 'restock' => true]],
        ], $this->kasir);

        $this->assertEquals(8, $p->fresh()->stock);       // 6 + 2
        $this->assertEquals(10000, (float) $ret->returned_total);
        $this->assertEquals(-10000, (float) $ret->balance);
        $this->assertEquals(10000, (float) $ret->refund_amount);
        $this->assertEquals(1, StockMovement::where('type', 'return_in')->count());
    }

    public function test_damaged_return_does_not_restock(): void
    {
        $p = $this->product(10, 5000);
        $trx = $this->sell($p, 4);          // stok -> 6
        $ti = $trx->items->first();

        $this->service->create([
            'transaction_id' => $trx->id,
            'returned_items' => [['transaction_item_id' => $ti->id, 'qty' => 2, 'restock' => false]],
        ], $this->kasir);

        $this->assertEquals(6, $p->fresh()->stock);        // tidak berubah
        $this->assertEquals(0, StockMovement::where('type', 'return_in')->count());
    }

    public function test_exchange_decrements_stock_and_computes_balance(): void
    {
        $sold = $this->product(10, 5000);
        $exchange = $this->product(10, 8000);
        $trx = $this->sell($sold, 2);       // sold stok -> 8
        $ti = $trx->items->first();

        // Retur 1 (5000) + tukar 1 (8000) -> balance +3000 (pelanggan bayar)
        $ret = $this->service->create([
            'transaction_id' => $trx->id,
            'returned_items' => [['transaction_item_id' => $ti->id, 'qty' => 1, 'restock' => true]],
            'exchange_items' => [['product_id' => $exchange->id, 'price_type' => 'umum', 'qty' => 1]],
            'payment_amount' => 3000,
        ], $this->kasir);

        $this->assertEquals(9, $sold->fresh()->stock);     // 8 + 1 restock
        $this->assertEquals(9, $exchange->fresh()->stock); // 10 - 1
        $this->assertEquals(3000, (float) $ret->balance);
        $this->assertEquals(3000, (float) $ret->payment_amount);
        $this->assertEquals(0, (float) $ret->refund_amount);
    }

    public function test_over_return_is_rejected(): void
    {
        $p = $this->product(10, 5000);
        $trx = $this->sell($p, 2);
        $ti = $trx->items->first();

        $this->expectException(ValidationException::class);
        $this->service->create([
            'transaction_id' => $trx->id,
            'returned_items' => [['transaction_item_id' => $ti->id, 'qty' => 5, 'restock' => true]],
        ], $this->kasir);
    }

    public function test_exchange_shortage_rolls_back(): void
    {
        $sold = $this->product(10, 5000);
        $exchange = $this->product(0, 8000); // stok 0
        $trx = $this->sell($sold, 2);
        $ti = $trx->items->first();

        try {
            $this->service->create([
                'transaction_id' => $trx->id,
                'returned_items' => [['transaction_item_id' => $ti->id, 'qty' => 1, 'restock' => true]],
                'exchange_items' => [['product_id' => $exchange->id, 'price_type' => 'umum', 'qty' => 1]],
                'payment_amount' => 99999,
            ], $this->kasir);
            $this->fail('Seharusnya melempar ValidationException.');
        } catch (ValidationException $e) {
            // rollback: stok sold tidak bertambah, tidak ada record retur
        }

        $this->assertEquals(8, $sold->fresh()->stock); // tetap (rollback restock)
        $this->assertDatabaseCount('returns', 0);
    }
}
