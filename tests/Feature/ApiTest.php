<?php

namespace Tests\Feature;

use App\Models\PriceType;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\PriceTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $kasir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
        $this->seed(PriceTypeSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->kasir = User::factory()->create();
        $this->kasir->assignRole('kasir');
    }

    public function test_guest_gets_401(): void
    {
        $this->getJson('/api/products')->assertStatus(401);
    }

    public function test_admin_can_create_product_with_prices(): void
    {
        $payload = [
            'name' => 'Kabel Baru',
            'sku' => 'KBL-NEW',
            'stock' => 100,
            'min_stock' => 10,
            'purchase_price' => 5000,
            'is_active' => true,
            'default_type' => 'umum',
            'prices' => [
                ['price_type' => 'umum', 'price' => 8000],
                ['price_type' => 'grosir', 'price' => 7000],
            ],
        ];

        $res = $this->actingAs($this->admin)->postJson('/api/products', $payload);

        $res->assertStatus(201)
            ->assertJsonPath('message', 'Produk berhasil disimpan')
            ->assertJsonPath('data.name', 'Kabel Baru')
            ->assertJsonPath('data.default_price.price', 8000);

        $this->assertDatabaseHas('products', ['sku' => 'KBL-NEW']);
        $this->assertDatabaseCount('product_prices', 2);
    }

    public function test_product_validation_error_returns_422(): void
    {
        $this->actingAs($this->admin)->postJson('/api/products', ['stock' => 'abc'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'stock', 'prices', 'default_type']);
    }

    public function test_kasir_cannot_create_product(): void
    {
        $this->actingAs($this->kasir)->postJson('/api/products', ['name' => 'X'])
            ->assertStatus(403);
    }

    public function test_kasir_can_view_but_not_manage_price_types(): void
    {
        // Kasir butuh lihat tipe harga (dropdown kasir) tapi tak boleh kelola.
        $this->actingAs($this->kasir)->getJson('/api/price-types')->assertStatus(200);
        $this->actingAs($this->kasir)->postJson('/api/price-types', ['name' => 'X'])->assertStatus(403);
    }

    public function test_index_is_paginated(): void
    {
        Product::factory()->count(3)->create()->each(function ($p) {
            $p->prices()->create(['price_type' => 'umum', 'price' => 1000, 'is_active_default' => true]);
        });

        $this->actingAs($this->admin)->getJson('/api/products')
            ->assertStatus(200)
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    public function test_kasir_can_create_transaction_and_stock_decrements(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $product->prices()->create(['price_type' => 'umum', 'price' => 12000, 'is_active_default' => true]);

        $payload = [
            'payment_amount' => 50000,
            'items' => [
                ['item_type' => 'product', 'product_id' => $product->id, 'price_type' => 'umum', 'qty' => 2],
            ],
        ];

        $res = $this->actingAs($this->kasir)->postJson('/api/transactions', $payload);

        $res->assertStatus(201)
            ->assertJsonPath('data.total', 24000)
            ->assertJsonPath('data.change_amount', 26000);

        $this->assertEquals(8, $product->fresh()->stock);
    }

    public function test_transaction_insufficient_stock_returns_422(): void
    {
        $product = Product::factory()->create(['stock' => 1]);
        $product->prices()->create(['price_type' => 'umum', 'price' => 12000, 'is_active_default' => true]);

        $this->actingAs($this->kasir)->postJson('/api/transactions', [
            'payment_amount' => 999999,
            'items' => [
                ['item_type' => 'product', 'product_id' => $product->id, 'price_type' => 'umum', 'qty' => 5],
            ],
        ])->assertStatus(422)->assertJsonValidationErrors(['items.0.qty']);
    }

    public function test_missing_resource_returns_json_404(): void
    {
        $this->actingAs($this->admin)->getJson('/api/products/999999')
            ->assertStatus(404)
            ->assertJsonStructure(['message']);
    }

    public function test_dashboard_stats(): void
    {
        $this->actingAs($this->admin)->getJson('/api/dashboard/stats')
            ->assertStatus(200)
            ->assertJsonStructure(['data' => ['total_products', 'total_customers', 'today_sales', 'low_stock_count']]);
    }

    public function test_kasir_can_create_customer(): void
    {
        $this->actingAs($this->kasir)->postJson('/api/customers', ['name' => 'Pelanggan Baru'])
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Pelanggan Baru');
    }

    public function test_admin_can_crud_supplier_and_delete_guarded(): void
    {
        $res = $this->actingAs($this->admin)->postJson('/api/suppliers', ['name' => 'PT Pemasok'])
            ->assertStatus(201)->assertJsonPath('data.name', 'PT Pemasok');
        $id = $res->json('data.id');

        $this->actingAs($this->admin)->deleteJson('/api/suppliers/' . $id)->assertStatus(200);
    }

    public function test_kasir_blocked_from_supplies_and_opname(): void
    {
        $this->actingAs($this->kasir)->getJson('/api/supplies')->assertStatus(403);
        $this->actingAs($this->kasir)->getJson('/api/stock-opnames')->assertStatus(403);
        $this->actingAs($this->kasir)->postJson('/api/suppliers', ['name' => 'X'])->assertStatus(403);
    }

    public function test_kasir_can_access_returns(): void
    {
        $this->actingAs($this->kasir)->getJson('/api/returns')->assertStatus(200);
    }

    public function test_admin_can_supply_and_stock_increments(): void
    {
        $supplier = \App\Models\Supplier::factory()->create();
        $product = Product::factory()->create(['stock' => 5]);
        $product->prices()->create(['price_type' => 'umum', 'price' => 5000, 'is_active_default' => true]);

        $this->actingAs($this->admin)->postJson('/api/supplies', [
            'supplier_id' => $supplier->id,
            'items' => [['product_id' => $product->id, 'qty' => 20, 'purchase_price' => 3000]],
        ])->assertStatus(201)->assertJsonPath('data.total_cost', 60000);

        $this->assertEquals(25, $product->fresh()->stock);
    }

    public function test_kasirs_endpoint_requires_view_all(): void
    {
        $this->actingAs($this->kasir)->getJson('/api/transactions/kasirs')->assertStatus(403);
        $this->actingAs($this->admin)->getJson('/api/transactions/kasirs')->assertStatus(200)->assertJsonStructure(['data']);
    }

    public function test_kasir_id_filter_limits_results(): void
    {
        $product = Product::factory()->create(['stock' => 100]);
        $product->prices()->create(['price_type' => 'umum', 'price' => 5000, 'is_active_default' => true]);
        $svc = app(\App\Services\TransactionService::class);
        $payload = ['payment_amount' => 99999, 'items' => [['item_type' => 'product', 'product_id' => $product->id, 'price_type' => 'umum', 'qty' => 1]]];
        $svc->create($payload, $this->admin);
        $svc->create($payload, $this->kasir);

        $res = $this->actingAs($this->admin)->getJson('/api/transactions?kasir_id=' . $this->kasir->id)->assertStatus(200);
        $this->assertCount(1, $res->json('data'));
        $this->assertEquals($this->kasir->id, $res->json('data.0.kasir_id'));
    }

    public function test_admin_can_export_transactions_xlsx(): void
    {
        $product = Product::factory()->create(['stock' => 100]);
        $product->prices()->create(['price_type' => 'umum', 'price' => 5000, 'is_active_default' => true]);
        app(\App\Services\TransactionService::class)->create(
            ['payment_amount' => 99999, 'items' => [['item_type' => 'product', 'product_id' => $product->id, 'price_type' => 'umum', 'qty' => 1]]],
            $this->admin
        );

        $res = $this->actingAs($this->admin)->get('/api/transactions/export');
        $res->assertStatus(200);
        $this->assertStringContainsString('.xlsx', $res->headers->get('content-disposition'));
    }

    public function test_admin_can_crud_role_with_permissions(): void
    {
        $res = $this->actingAs($this->admin)->postJson('/api/roles', [
            'name' => 'supervisor',
            'permissions' => ['dashboard.view', 'products.view'],
        ])->assertStatus(201)->assertJsonPath('data.name', 'supervisor');
        $id = $res->json('data.id');

        $this->assertTrue(\Spatie\Permission\Models\Role::findByName('supervisor')->hasPermissionTo('products.view'));

        // Sync ulang: kurangi jadi satu permission.
        $this->actingAs($this->admin)->putJson('/api/roles/' . $id, ['name' => 'supervisor', 'permissions' => ['dashboard.view']])
            ->assertStatus(200);
        $this->assertFalse(\Spatie\Permission\Models\Role::findByName('supervisor')->hasPermissionTo('products.view'));

        $this->actingAs($this->admin)->deleteJson('/api/roles/' . $id)->assertStatus(200);
        $this->assertDatabaseMissing('roles', ['name' => 'supervisor']);
    }

    public function test_core_role_cannot_be_deleted(): void
    {
        $adminRole = \Spatie\Permission\Models\Role::findByName('admin');
        $this->actingAs($this->admin)->deleteJson('/api/roles/' . $adminRole->id)->assertStatus(422);
    }

    public function test_kasir_blocked_from_roles(): void
    {
        $this->actingAs($this->kasir)->getJson('/api/roles')->assertStatus(403);
        $this->actingAs($this->kasir)->getJson('/api/permissions')->assertStatus(403);
    }

    public function test_permissions_endpoint_lists_permissions(): void
    {
        $this->actingAs($this->admin)->getJson('/api/permissions')
            ->assertStatus(200)
            ->assertJsonStructure(['data']);
    }

    public function test_admin_can_import_products_via_csv(): void
    {
        $csv = "name,sku,stock,purchase_price,umum,grosir\nProduk Impor,IMP-1,10,4000,7000,6500\n";
        $path = tempnam(sys_get_temp_dir(), 'imp') . '.csv';
        file_put_contents($path, $csv);
        $file = new \Illuminate\Http\UploadedFile($path, 'import.csv', 'text/csv', null, true);

        $this->actingAs($this->admin)->post('/api/products/import', ['file' => $file])
            ->assertStatus(200)
            ->assertJsonPath('data.created', 1);

        $this->assertDatabaseHas('products', ['sku' => 'IMP-1', 'stock' => 10]);
        $product = Product::where('sku', 'IMP-1')->first();
        $this->assertEquals(7000, (float) $product->prices()->where('price_type', 'umum')->value('price'));
        $this->assertEquals(6500, (float) $product->prices()->where('price_type', 'grosir')->value('price'));
    }

    public function test_import_requires_file(): void
    {
        $this->actingAs($this->admin)->postJson('/api/products/import', [])
            ->assertStatus(422)->assertJsonValidationErrors(['file']);
    }

    public function test_kasir_cannot_import_products(): void
    {
        $this->actingAs($this->kasir)->postJson('/api/products/import', [])->assertStatus(403);
    }

    public function test_admin_can_download_import_template(): void
    {
        $res = $this->actingAs($this->admin)->get('/api/products/import-template');
        $res->assertStatus(200);
        $this->assertStringContainsString('.xlsx', $res->headers->get('content-disposition'));
    }

    public function test_history_sheet_computes_profit_and_grand_total(): void
    {
        $product = Product::factory()->create(['stock' => 100, 'purchase_price' => 4000]);
        $product->prices()->create(['price_type' => 'umum', 'price' => 10000, 'is_active_default' => true]);
        $trx = app(\App\Services\TransactionService::class)->create(
            ['payment_amount' => 99999, 'items' => [['item_type' => 'product', 'product_id' => $product->id, 'price_type' => 'umum', 'qty' => 2]]],
            $this->admin
        );

        $collection = \App\Models\Transaction::with('items.product', 'kasir', 'customer')->whereKey($trx->id)->get();
        $rows = (new \App\Exports\Sheets\TransactionHistorySheet($collection))->array();

        // Baris data: total 20000, profit (10000-4000)*2 = 12000.
        $this->assertEquals(20000, $rows[0][6]);
        $this->assertEquals(12000, $rows[0][7]);
        // Baris terakhir = TOTAL.
        $last = end($rows);
        $this->assertEquals('TOTAL', $last[5]);
        $this->assertEquals(20000, $last[6]);
        $this->assertEquals(12000, $last[7]);
    }
}
