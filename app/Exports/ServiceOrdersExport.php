<?php

namespace App\Exports;

use App\Exports\Sheets\ServiceOrderHistorySheet;
use App\Exports\Sheets\ServiceOrderItemsSheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class ServiceOrdersExport implements WithMultipleSheets
{
    /**
     * @param  Collection<int,\App\Models\ServiceOrder>  $orders  (sudah eager-load items, customer, technician, operator)
     */
    public function __construct(private readonly Collection $orders)
    {
    }

    public function sheets(): array
    {
        return [
            new ServiceOrderHistorySheet($this->orders),
            new ServiceOrderItemsSheet($this->orders),
        ];
    }
}
