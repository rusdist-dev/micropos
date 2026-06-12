<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolePermissionSeeder::class);
    }

    protected function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    /**
     * @dataProvider routeProvider
     */
    public function test_pages_render_for_admin(string $uri): void
    {
        $this->actingAs($this->admin())->get($uri)->assertStatus(200);
    }

    public static function routeProvider(): array
    {
        return [
            'dashboard'      => ['/dashboard'],
            'products'       => ['/products'],
            'products.create'=> ['/products/create'],
            'products.edit'  => ['/products/1/edit'],
            'price-types'    => ['/price-types'],
            'price-types.create' => ['/price-types/create'],
            'price-types.edit'   => ['/price-types/1/edit'],
            'categories'     => ['/categories'],
            'categories.create' => ['/categories/create'],
            'categories.edit'   => ['/categories/1/edit'],
            'customers'      => ['/customers'],
            'customers.create'=> ['/customers/create'],
            'suppliers'      => ['/suppliers'],
            'suppliers.create'=> ['/suppliers/create'],
            'suppliers.edit' => ['/suppliers/1/edit'],
            'stock-opnames'  => ['/stock-opnames'],
            'stock-opnames.create' => ['/stock-opnames/create'],
            'stock-opnames.show'   => ['/stock-opnames/1'],
            'supplies'       => ['/supplies'],
            'supplies.create'=> ['/supplies/create'],
            'supplies.show'  => ['/supplies/1'],
            'returns'        => ['/returns'],
            'returns.create' => ['/returns/create'],
            'returns.show'   => ['/returns/1'],
            'services'       => ['/services'],
            'cashier'        => ['/cashier'],
            'transactions'   => ['/transactions'],
            'transactions.show' => ['/transactions/1'],
            'users'          => ['/users'],
            'users.create'   => ['/users/create'],
            'roles'          => ['/roles'],
            'roles.create'   => ['/roles/create'],
            'roles.edit'     => ['/roles/1/edit'],
            'profile'        => ['/profile'],
        ];
    }

    public function test_guest_redirected_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertStatus(200);
    }
}
