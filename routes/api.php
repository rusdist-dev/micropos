<?php

use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\PriceTypeController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ReturnController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\StockOpnameController;
use App\Http\Controllers\Api\SupplierController;
use App\Http\Controllers\Api\SupplyController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use App\Models\ReturnTransaction;
use Illuminate\Support\Facades\Route;

// Binding eksplisit: segment {return} -> model ReturnTransaction.
Route::bind('return', fn ($id) => ReturnTransaction::findOrFail($id));

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Dikonsumsi oleh Blade via Alpine fetch() (same-origin). Memakai middleware
| 'web' (session + CSRF) + 'auth', lalu permission Spatie per route.
*/

Route::middleware(['web', 'auth'])->group(function () {
    // Dashboard
    Route::middleware('permission:dashboard.view')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/dashboard/stats', [DashboardController::class, 'stats']);
        Route::get('/dashboard/sales-chart', [DashboardController::class, 'salesChart']);
        Route::get('/dashboard/top-products', [DashboardController::class, 'topProducts']);
    });

    // Produk
    Route::get('/products', [ProductController::class, 'index'])->middleware('permission:products.view');
    Route::post('/products', [ProductController::class, 'store'])->middleware('permission:products.create');
    // Import (rute spesifik sebelum wildcard /{product}).
    Route::get('/products/import-template', [ProductController::class, 'importTemplate'])->middleware('permission:products.view');
    Route::post('/products/import', [ProductController::class, 'import'])->middleware('permission:products.create');
    Route::get('/products/{product}', [ProductController::class, 'show'])->middleware('permission:products.view');
    Route::match(['put', 'patch'], '/products/{product}', [ProductController::class, 'update'])->middleware('permission:products.edit');
    Route::delete('/products/{product}', [ProductController::class, 'destroy'])->middleware('permission:products.delete');

    // Tipe Harga (master)
    Route::get('/price-types', [PriceTypeController::class, 'index'])->middleware('permission:price-types.view');
    Route::post('/price-types', [PriceTypeController::class, 'store'])->middleware('permission:price-types.create');
    Route::get('/price-types/{priceType}', [PriceTypeController::class, 'show'])->middleware('permission:price-types.view');
    Route::match(['put', 'patch'], '/price-types/{priceType}', [PriceTypeController::class, 'update'])->middleware('permission:price-types.edit');
    Route::delete('/price-types/{priceType}', [PriceTypeController::class, 'destroy'])->middleware('permission:price-types.delete');

    // Pemasok
    Route::get('/suppliers', [SupplierController::class, 'index'])->middleware('permission:suppliers.view');
    Route::post('/suppliers', [SupplierController::class, 'store'])->middleware('permission:suppliers.create');
    Route::get('/suppliers/{supplier}', [SupplierController::class, 'show'])->middleware('permission:suppliers.view');
    Route::match(['put', 'patch'], '/suppliers/{supplier}', [SupplierController::class, 'update'])->middleware('permission:suppliers.edit');
    Route::delete('/suppliers/{supplier}', [SupplierController::class, 'destroy'])->middleware('permission:suppliers.delete');

    // Stok Opname
    Route::get('/stock-opnames', [StockOpnameController::class, 'index'])->middleware('permission:stock-opnames.view');
    Route::post('/stock-opnames', [StockOpnameController::class, 'store'])->middleware('permission:stock-opnames.create');
    Route::get('/stock-opnames/{stockOpname}', [StockOpnameController::class, 'show'])->middleware('permission:stock-opnames.view');
    Route::match(['put', 'patch'], '/stock-opnames/{stockOpname}', [StockOpnameController::class, 'update'])->middleware('permission:stock-opnames.edit');
    Route::post('/stock-opnames/{stockOpname}/finalize', [StockOpnameController::class, 'finalize'])->middleware('permission:stock-opnames.finalize');

    // Supply Barang (immutable: tanpa update/delete)
    Route::get('/supplies', [SupplyController::class, 'index'])->middleware('permission:supplies.view');
    Route::post('/supplies', [SupplyController::class, 'store'])->middleware('permission:supplies.create');
    Route::get('/supplies/{supply}', [SupplyController::class, 'show'])->middleware('permission:supplies.view');

    // Retur Barang
    Route::get('/returns', [ReturnController::class, 'index'])->middleware('permission:returns.view');
    Route::post('/returns', [ReturnController::class, 'store'])->middleware('permission:returns.create');
    Route::get('/returns/{return}', [ReturnController::class, 'show'])->middleware('permission:returns.view');
    Route::get('/transactions/{transaction}/returnable', [ReturnController::class, 'returnable'])->middleware('permission:returns.create');

    // Riwayat pergerakan stok per produk
    Route::get('/products/{product}/stock-movements', [ProductController::class, 'stockMovements'])->middleware('permission:products.view');

    // Pelanggan
    Route::get('/customers', [CustomerController::class, 'index'])->middleware('permission:customers.view');
    Route::post('/customers', [CustomerController::class, 'store'])->middleware('permission:customers.create');
    Route::get('/customers/{customer}', [CustomerController::class, 'show'])->middleware('permission:customers.view');
    Route::match(['put', 'patch'], '/customers/{customer}', [CustomerController::class, 'update'])->middleware('permission:customers.edit');
    Route::delete('/customers/{customer}', [CustomerController::class, 'destroy'])->middleware('permission:customers.delete');

    // Jasa
    Route::get('/services', [ServiceController::class, 'index'])->middleware('permission:services.view');
    Route::post('/services', [ServiceController::class, 'store'])->middleware('permission:services.create');
    Route::get('/services/{service}', [ServiceController::class, 'show'])->middleware('permission:services.view');
    Route::match(['put', 'patch'], '/services/{service}', [ServiceController::class, 'update'])->middleware('permission:services.edit');
    Route::delete('/services/{service}', [ServiceController::class, 'destroy'])->middleware('permission:services.delete');

    // Transaksi
    Route::get('/transactions', [TransactionController::class, 'index'])->middleware('permission:transactions.view');
    Route::post('/transactions', [TransactionController::class, 'store'])->middleware('permission:transactions.create');
    // Rute spesifik harus sebelum /{transaction} agar tidak tertangkap wildcard.
    Route::get('/transactions/export', [TransactionController::class, 'export'])->middleware('permission:transactions.view');
    Route::get('/transactions/kasirs', [TransactionController::class, 'kasirs'])->middleware('permission:transactions.view-all');
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->middleware('permission:transactions.view');

    // Role & Hak Akses
    Route::get('/permissions', [RoleController::class, 'permissions'])->middleware('permission:roles.view');
    Route::get('/roles', [RoleController::class, 'index'])->middleware('permission:roles.view');
    Route::post('/roles', [RoleController::class, 'store'])->middleware('permission:roles.create');
    Route::get('/roles/{role}', [RoleController::class, 'show'])->middleware('permission:roles.view');
    Route::match(['put', 'patch'], '/roles/{role}', [RoleController::class, 'update'])->middleware('permission:roles.edit');
    Route::delete('/roles/{role}', [RoleController::class, 'destroy'])->middleware('permission:roles.delete');

    // Pengguna
    Route::get('/users', [UserController::class, 'index'])->middleware('permission:users.view');
    Route::post('/users', [UserController::class, 'store'])->middleware('permission:users.create');
    Route::get('/users/{user}', [UserController::class, 'show'])->middleware('permission:users.view');
    Route::match(['put', 'patch'], '/users/{user}', [UserController::class, 'update'])->middleware('permission:users.edit');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->middleware('permission:users.delete');
});
