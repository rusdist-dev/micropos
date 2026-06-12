<?php

namespace App\Providers;

use App\Models\StockOpname;
use App\Models\Supply;
use App\Models\Transaction;
use App\Models\ReturnTransaction;
use App\Support\AppSettings;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AppSettings::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Konfigurasi aplikasi tersedia di seluruh view sebagai $appSettings
        // (dipakai partial tema, sidebar, dan injeksi window.posSettings).
        View::share('appSettings', $this->app->make(AppSettings::class));

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
