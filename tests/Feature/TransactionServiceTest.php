<?php

namespace Tests\Feature;

use App\Models\PriceType;
use App\Models\Product;
use App\Models\User;
use App\Services\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    private TransactionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TransactionService::class);

        PriceType::create(['code' => 'umum', 'name' => 'Umum', 'sort_order' => 1]);
        PriceType::create(['code' => 'grosir', 'name' => 'Grosir', 'sort_order' => 2]);
    }

    private function makeProduct(int $stock = 10, array $prices = ['umum' => 10000, 'grosir' => 9000]): Product
    {
        $product = Product::create([
            'name' => 'Produk Uji',
            'stock' => $stock,
            'min_stock' => 1,
            'purchase_price' => 5000,
            'is_active' => true,
        ]);

        foreach ($prices as $type => $price) {
            $product->prices()->create([
                'price_type' => $type,
                'price' => $price,
                'is_active_default' => $type === 'umum',
            ]);
        }

        return $product;
    }

    public function test_creates_transaction_with_authoritative_price_and_decrements_stock(): void
    {
        $kasir = User::factory()->create();
        $product = $this->makeProduct(stock: 10);

        $trx = $this->service->create([
            'customer_id' => null,
            'payment_amount' => 50000,
            'items' => [
                // Harga dari client diabaikan; server pakai harga DB (grosir = 9000).
                ['item_type' => 'product', 'product_id' => $product->id, 'price_type' => 'grosir', 'qty' => 3, 'price' => 1],
            ],
        ], $kasir);

        $this->assertEquals(27000, $trx->total);          // 9000 * 3
        $this->assertEquals(23000, $trx->change_amount);   // 50000 - 27000
        $this->assertEquals($kasir->id, $trx->kasir_id);

        $item = $trx->items->first();
        $this->assertEquals(9000, $item->price_snapshot);
        $this->assertEquals('grosir', $item->price_type_used);
        $this->assertEquals(27000, $item->subtotal);

        $this->assertEquals(7, $product->fresh()->stock); // 10 - 3
        $this->assertMatchesRegularExpression('/^INV-\d{8}-00001$/', $trx->invoice_number);
    }

    public function test_service_item_uses_provided_price(): void
    {
        $kasir = User::factory()->create();

        $trx = $this->service->create([
            'payment_amount' => 200000,
            'items' => [
                ['item_type' => 'service', 'service_id' => null, 'item_name' => 'Instalasi', 'price' => 150000, 'qty' => 1],
            ],
        ], $kasir);

        $this->assertEquals(150000, $trx->total);
        $item = $trx->items->first();
        $this->assertEquals('service', $item->item_type->value);
        $this->assertNull($item->price_type_used);
        $this->assertEquals('Instalasi', $item->item_name);
    }

    public function test_rejects_insufficient_stock(): void
    {
        $kasir = User::factory()->create();
        $product = $this->makeProduct(stock: 2);

        $this->expectException(ValidationException::class);

        $this->service->create([
            'payment_amount' => 999999,
            'items' => [
                ['item_type' => 'product', 'product_id' => $product->id, 'price_type' => 'umum', 'qty' => 5],
            ],
        ], $kasir);

        // Stok tidak boleh berubah saat transaksi gagal.
        $this->assertEquals(2, $product->fresh()->stock);
    }

    public function test_rejects_payment_less_than_total(): void
    {
        $kasir = User::factory()->create();
        $product = $this->makeProduct(stock: 10);

        $this->expectException(ValidationException::class);

        $this->service->create([
            'payment_amount' => 1000,
            'items' => [
                ['item_type' => 'product', 'product_id' => $product->id, 'price_type' => 'umum', 'qty' => 1],
            ],
        ], $kasir);
    }

    public function test_invoice_number_increments_per_day(): void
    {
        $kasir = User::factory()->create();
        $product = $this->makeProduct(stock: 100);

        $payload = [
            'payment_amount' => 100000,
            'items' => [['item_type' => 'product', 'product_id' => $product->id, 'price_type' => 'umum', 'qty' => 1]],
        ];

        $first = $this->service->create($payload, $kasir);
        $second = $this->service->create($payload, $kasir);

        $this->assertStringEndsWith('-00001', $first->invoice_number);
        $this->assertStringEndsWith('-00002', $second->invoice_number);
    }
}
