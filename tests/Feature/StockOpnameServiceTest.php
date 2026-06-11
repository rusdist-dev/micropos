<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Services\StockOpnameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StockOpnameServiceTest extends TestCase
{
    use RefreshDatabase;

    private StockOpnameService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(StockOpnameService::class);
    }

    private function product(int $stock): Product
    {
        return Product::create(['name' => 'P' . $stock, 'stock' => $stock, 'min_stock' => 1, 'purchase_price' => 0, 'is_active' => true]);
    }

    public function test_draft_snapshots_system_qty(): void
    {
        $admin = User::factory()->create();
        $p = $this->product(20);

        $opname = $this->service->createDraft([], $admin);

        $item = $opname->items()->where('product_id', $p->id)->first();
        $this->assertNotNull($item);
        $this->assertEquals(20, $item->system_qty);
        $this->assertNull($item->counted_qty);
        $this->assertEquals('draft', $opname->status->value);
    }

    public function test_finalize_syncs_stock_and_records_movement(): void
    {
        $admin = User::factory()->create();
        $p = $this->product(20);

        $opname = $this->service->createDraft([], $admin);
        $this->service->updateCounts($opname, ['items' => [['product_id' => $p->id, 'counted_qty' => 17]]]);
        $opname = $this->service->finalize($opname->fresh(), $admin);

        $this->assertEquals('completed', $opname->status->value);
        $this->assertEquals(17, $p->fresh()->stock);

        $item = $opname->items()->where('product_id', $p->id)->first();
        $this->assertEquals(-3, $item->difference);

        $movement = StockMovement::where('type', 'opname')->first();
        $this->assertEquals(-3, $movement->qty_change);
        $this->assertEquals(20, $movement->stock_before);
        $this->assertEquals(17, $movement->stock_after);
    }

    public function test_cannot_update_or_finalize_completed_opname(): void
    {
        $admin = User::factory()->create();
        $p = $this->product(5);
        $opname = $this->service->createDraft([], $admin);
        $this->service->updateCounts($opname, ['items' => [['product_id' => $p->id, 'counted_qty' => 5]]]);
        $opname = $this->service->finalize($opname->fresh(), $admin);

        $this->expectException(ValidationException::class);
        $this->service->finalize($opname->fresh(), $admin);
    }

    public function test_finalize_recomputes_against_current_stock(): void
    {
        // Snapshot 20, lalu stok berubah (penjualan interim) jadi 18, counted 19.
        $admin = User::factory()->create();
        $p = $this->product(20);
        $opname = $this->service->createDraft([], $admin);
        $this->service->updateCounts($opname, ['items' => [['product_id' => $p->id, 'counted_qty' => 19]]]);

        $p->update(['stock' => 18]); // perubahan interim

        $opname = $this->service->finalize($opname->fresh(), $admin);
        $item = $opname->items()->where('product_id', $p->id)->first();

        $this->assertEquals(19, $p->fresh()->stock);     // di-set absolut ke counted
        $this->assertEquals(18, $item->system_qty);       // diperbarui ke stok terkini
        $this->assertEquals(1, $item->difference);        // 19 - 18
    }
}
