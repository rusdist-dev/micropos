<?php

namespace Tests\Feature;

use App\Models\PriceType;
use App\Models\Product;
use App\Services\ProductImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductImportServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProductImportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ProductImportService::class);
        PriceType::create(['code' => 'umum', 'name' => 'Umum', 'sort_order' => 1]);
        PriceType::create(['code' => 'grosir', 'name' => 'Grosir', 'sort_order' => 2]);
    }

    public function test_imports_products_with_prices_and_default(): void
    {
        $rows = [
            ['name', 'sku', 'stock', 'purchase_price', 'umum', 'grosir'],
            ['Produk A', 'PA', 10, 4000, 7000, 6500],
            ['Produk B', '', 5, 1000, 1500, ''], // tanpa sku, grosir kosong
        ];

        $result = $this->service->import($rows);

        $this->assertEquals(2, $result['created']);
        $this->assertEquals(0, $result['updated']);
        $this->assertEmpty($result['failed']);

        $a = Product::where('sku', 'PA')->first();
        $this->assertEquals(10, $a->stock);
        $this->assertEquals(7000, (float) $a->prices()->where('price_type', 'umum')->value('price'));
        $this->assertEquals(6500, (float) $a->prices()->where('price_type', 'grosir')->value('price'));
        $this->assertTrue((bool) $a->prices()->where('price_type', 'umum')->value('is_active_default'));

        $b = Product::where('name', 'Produk B')->first();
        $this->assertEquals(1, $b->prices()->count()); // hanya umum
        $this->assertTrue((bool) $b->prices()->where('price_type', 'umum')->value('is_active_default'));
    }

    public function test_existing_sku_is_updated_not_duplicated(): void
    {
        Product::create(['name' => 'Lama', 'sku' => 'PA', 'stock' => 1, 'min_stock' => 0, 'purchase_price' => 0, 'is_active' => true]);

        $result = $this->service->import([
            ['name', 'sku', 'stock', 'umum'],
            ['Produk A Baru', 'PA', 99, 8000],
        ]);

        $this->assertEquals(0, $result['created']);
        $this->assertEquals(1, $result['updated']);
        $this->assertEquals(1, Product::where('sku', 'PA')->count());
        $this->assertEquals(99, Product::where('sku', 'PA')->value('stock'));
    }

    public function test_row_without_name_is_reported_as_failed(): void
    {
        $result = $this->service->import([
            ['name', 'sku', 'umum'],
            ['', 'X1', 5000],
        ]);

        $this->assertEquals(0, $result['created']);
        $this->assertCount(1, $result['failed']);
        $this->assertEquals(2, $result['failed'][0]['row']);
    }

    public function test_missing_name_column_aborts(): void
    {
        $result = $this->service->import([
            ['sku', 'umum'],
            ['X1', 5000],
        ]);

        $this->assertEquals(0, $result['created']);
        $this->assertNotEmpty($result['failed']);
    }
}
