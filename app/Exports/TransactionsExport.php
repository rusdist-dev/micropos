<?php

namespace App\Exports;

use App\Exports\Sheets\TransactionHistorySheet;
use App\Exports\Sheets\TransactionItemsSheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class TransactionsExport implements WithMultipleSheets
{
    /**
     * @param  Collection<int,\App\Models\Transaction>  $transactions  (sudah eager-load items.product, kasir, customer)
     */
    public function __construct(private readonly Collection $transactions)
    {
    }

    public function sheets(): array
    {
        return [
            new TransactionHistorySheet($this->transactions),
            new TransactionItemsSheet($this->transactions),
        ];
    }
}
