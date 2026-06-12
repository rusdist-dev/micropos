<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Web\CashierController;
use App\Http\Controllers\Web\CategoryController;
use App\Http\Controllers\Web\CustomerController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\PriceTypeController;
use App\Http\Controllers\Web\ProductController;
use App\Http\Controllers\Web\ReturnController;
use App\Http\Controllers\Web\RoleController;
use App\Http\Controllers\Web\ServiceController;
use App\Http\Controllers\Web\SettingController;
use App\Http\Controllers\Web\StockOpnameController;
use App\Http\Controllers\Web\SupplierController;
use App\Http\Controllers\Web\SupplyController;
use App\Http\Controllers\Web\TransactionController;
use App\Http\Controllers\Web\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
| Controller Web HANYA me-return view (lihat context.md 1.2). Seluruh data
| dimuat oleh Blade via Alpine.js fetch() ke endpoint API.
*/

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Produk
    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/create', [ProductController::class, 'create'])->name('products.create');
    Route::get('/products/{id}/edit', [ProductController::class, 'edit'])->name('products.edit');

    // Kategori (master)
    Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
    Route::get('/categories/create', [CategoryController::class, 'create'])->name('categories.create');
    Route::get('/categories/{id}/edit', [CategoryController::class, 'edit'])->name('categories.edit');

    // Pelanggan
    Route::get('/customers', [CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/create', [CustomerController::class, 'create'])->name('customers.create');
    Route::get('/customers/{id}/edit', [CustomerController::class, 'edit'])->name('customers.edit');

    // Tipe Harga (master)
    Route::get('/price-types', [PriceTypeController::class, 'index'])->name('price-types.index');
    Route::get('/price-types/create', [PriceTypeController::class, 'create'])->name('price-types.create');
    Route::get('/price-types/{id}/edit', [PriceTypeController::class, 'edit'])->name('price-types.edit');

    // Pemasok
    Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    Route::get('/suppliers/create', [SupplierController::class, 'create'])->name('suppliers.create');
    Route::get('/suppliers/{id}/edit', [SupplierController::class, 'edit'])->name('suppliers.edit');

    // Stok Opname
    Route::get('/stock-opnames', [StockOpnameController::class, 'index'])->name('stock-opnames.index');
    Route::get('/stock-opnames/create', [StockOpnameController::class, 'create'])->name('stock-opnames.create');
    Route::get('/stock-opnames/{id}', [StockOpnameController::class, 'show'])->name('stock-opnames.show');

    // Supply Barang
    Route::get('/supplies', [SupplyController::class, 'index'])->name('supplies.index');
    Route::get('/supplies/create', [SupplyController::class, 'create'])->name('supplies.create');
    Route::get('/supplies/{id}', [SupplyController::class, 'show'])->name('supplies.show');

    // Retur Barang
    Route::get('/returns', [ReturnController::class, 'index'])->name('returns.index');
    Route::get('/returns/create', [ReturnController::class, 'create'])->name('returns.create');
    Route::get('/returns/{id}', [ReturnController::class, 'show'])->name('returns.show');

    // Jasa
    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');

    // Kasir
    Route::get('/cashier', [CashierController::class, 'index'])->name('cashier.index');

    // Riwayat Transaksi
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{id}', [TransactionController::class, 'show'])->name('transactions.show');

    // Konfigurasi Toko
    Route::get('/settings', [SettingController::class, 'index'])
        ->middleware('permission:settings.view')
        ->name('settings.index');

    // Role & Hak Akses
    Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('/roles/create', [RoleController::class, 'create'])->name('roles.create');
    Route::get('/roles/{id}/edit', [RoleController::class, 'edit'])->name('roles.edit');

    // Pengguna
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::get('/users/create', [UserController::class, 'create'])->name('users.create');
    Route::get('/users/{id}/edit', [UserController::class, 'edit'])->name('users.edit');

    // Profil (Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
