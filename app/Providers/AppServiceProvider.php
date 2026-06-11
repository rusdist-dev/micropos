<?php

namespace App\Providers;

use App\Models\StockOpname;
use App\Models\Supply;
use App\Models\Transaction;
use App\Models\ReturnTransaction;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Key morph stabil untuk kolom reference_type pada stock_movements.
        // morphMap (non-enforced) agar model lain (mis. User milik Spatie) tetap memakai FQCN.
        Relation::morphMap([
            'transaction' => Transaction::class,
            'opname' => StockOpname::class,
            'supply' => Supply::class,
            'return' => ReturnTransaction::class,
        ]);
    }
}
