<?php

namespace Tests\Feature;

use App\Models\PriceType;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\User;
use App\Services\SupplyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplyServiceTest extends TestCase
{
    use RefreshDatabase;

    private SupplyService $service;
    private Supplier $supplier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SupplyService::class);
        PriceType::create(['code' => 'umum', 'name' => 'Umum', 'sort_order' => 1]);
        PriceType::create(['code' => 'grosir', 'name' => 'Grosir', 'sort_order' => 2]);
        $this->supplier = Supplier::create(['name' => 'Sup', 'is_active' => true]);
    }

    private function product(int $stock, float $cost): Product
    {
        $p = Product::create(['name' => 'P', 'stock' => $stock, 'min_stock' => 1, 'purchase_price' => $cost, 'is_active' => true]);
        $p->prices()->create(['price_type' => 'umum', 'price' => 5000, 'is_active_default' => true]);

        return $p;
    }

    public function test_supply_increments_stock_updates_cost_and_prices(): void
    {
        $admin = User::factory()->create();
        $p = $this->product(stock: 10, cost: 4000);

        $supply = $this->service->create([
            'supplier_id' => $this->supplier->id,
            'items' => [[
                'product_id' => $p->id,
                'qty' => 50,
                'purchase_price' => 4500,
                'prices' => [
                    ['price_type' => 'umum', 'price' => 7000],
                    ['price_type' => 'grosir', 'price' => 6300],
                ],
            ]],
        ], $admin);

        $p->refresh();
        $this->assertEquals(60, $p->stock);                 // 10 + 50
        $this->assertEquals(4500, (float) $p->purchase_price);
        $this->assertEquals(7000, (float) $p->prices()->where('price_type', 'umum')->value('price'));
        $this->assertEquals(6300, (float) $p->prices()->where('price_type', 'grosir')->value('price'));

        // umum tetap default (upsert tak mengubah default)
        $this->assertTrue((bool) $p->prices()->where('price_type', 'umum')->value('is_active_default'));

        $this->assertEquals(225000, (float) $supply->total_cost); // 50 * 4500

        $movement = StockMovement::where('type', 'supply')->first();
        $this->assertEquals(50, $movement->qty_change);
        $this->assertEquals(10, $movement->stock_before);
        $this->assertEquals(60, $movement->stock_after);
    }

    public function test_supply_without_new_cost_keeps_existing(): void
    {
        $admin = User::factory()->create();
        $p = $this->product(stock: 5, cost: 4000);

        $supply = $this->service->create([
            'supplier_id' => $this->supplier->id,
            'items' => [['product_id' => $p->id, 'qty' => 10]],
        ], $admin);

        $p->refresh();
        $this->assertEquals(15, $p->stock);
        $this->assertEquals(4000, (float) $p->purchase_price); // tidak berubah
        $this->assertEquals(40000, (float) $supply->total_cost); // 10 * 4000
    }
}
