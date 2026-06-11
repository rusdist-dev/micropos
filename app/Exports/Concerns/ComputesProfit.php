<?php

namespace App\Exports\Concerns;

use App\Enums\ItemType;
use App\Models\TransactionItem;

/**
 * Perhitungan profit = (harga jual snapshot - harga beli) * qty.
 * Harga beli diambil dari purchase_price produk SAAT INI (snapshot biaya tidak
 * disimpan di transaction_items). Jasa & produk terhapus dianggap biaya 0.
 */
trait ComputesProfit
{
    protected function itemCost(TransactionItem $item): float
    {
        if ($item->item_type === ItemType::Product && $item->product) {
            return (float) $item->product->purchase_price;
        }

        return 0.0;
    }

    protected function itemProfit(TransactionItem $item): float
    {
        return ((float) $item->price_snapshot - $this->itemCost($item)) * (int) $item->qty;
    }
}
