<?php

namespace App\Services;

use App\Enums\ItemType;
use App\Enums\ReturnDirection;
use App\Enums\StockMovementType;
use App\Models\Product;
use App\Models\ReturnItem;
use App\Models\ReturnTransaction;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Support\SequenceGenerator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReturnService
{
    public function __construct(
        private readonly StockMovementService $movements,
        private readonly SequenceGenerator $sequences,
    ) {
    }

    /**
     * Item produk yang masih bisa diretur dari sebuah transaksi (qty sisa > 0).
     */
    public function returnable(Transaction $transaction): Collection
    {
        $transaction->loadMissing('items');

        $returnedPerItem = ReturnItem::query()
            ->where('direction', ReturnDirection::Returned->value)
            ->whereIn('transaction_item_id', $transaction->items->pluck('id'))
            ->selectRaw('transaction_item_id, SUM(qty) as qty')
            ->groupBy('transaction_item_id')
            ->pluck('qty', 'transaction_item_id');

        return $transaction->items
            ->filter(fn ($it) => $it->item_type === ItemType::Product && $it->product_id !== null)
            ->map(function ($it) use ($returnedPerItem) {
                $already = (int) ($returnedPerItem[$it->id] ?? 0);

                return [
                    'transaction_item_id' => $it->id,
                    'product_id' => $it->product_id,
                    'item_name' => $it->item_name,
                    'price_type_used' => $it->price_type_used,
                    'price_snapshot' => (float) $it->price_snapshot,
                    'sold_qty' => (int) $it->qty,
                    'returned_qty' => $already,
                    'remaining_qty' => (int) $it->qty - $already,
                ];
            })
            ->filter(fn ($row) => $row['remaining_qty'] > 0)
            ->values();
    }

    /**
     * Proses retur/penukaran dalam satu DB transaction.
     *
     * @param  array{transaction_id:int,returned_items?:array,exchange_items?:array,payment_amount?:float,note?:string|null}  $data
     */
    public function create(array $data, User $kasir): ReturnTransaction
    {
        return DB::transaction(function () use ($data, $kasir) {
            /** @var Transaction $trx */
            $trx = Transaction::with('items')->findOrFail($data['transaction_id']);
            $returnedInput = $data['returned_items'] ?? [];
            $exchangeInput = $data['exchange_items'] ?? [];

            // Kunci semua produk yang stoknya akan berubah (restock + tukar), urut id.
            $productIds = collect();
            foreach ($returnedInput as $r) {
                if (! empty($r['restock'])) {
                    $ti = $trx->items->firstWhere('id', $r['transaction_item_id'] ?? null);
                    if ($ti && $ti->product_id) {
                        $productIds->push($ti->product_id);
                    }
                }
            }
            foreach ($exchangeInput as $e) {
                $productIds->push($e['product_id']);
            }
            $locked = Product::whereIn('id', $productIds->unique()->sort()->values())
                ->lockForUpdate()->get()->keyBy('id');

            // --- Resolusi item dikembalikan ---
            $resolvedReturned = [];
            $returnedTotal = 0;
            foreach ($returnedInput as $i => $r) {
                $ti = $trx->items->firstWhere('id', $r['transaction_item_id'] ?? null);
                if (! $ti) {
                    throw ValidationException::withMessages(["returned_items.$i.transaction_item_id" => 'Item tidak ada pada transaksi asal.']);
                }
                if ($ti->item_type !== ItemType::Product || ! $ti->product_id) {
                    throw ValidationException::withMessages(["returned_items.$i" => 'Hanya item produk yang dapat diretur.']);
                }

                $qty = (int) $r['qty'];
                $already = (int) ReturnItem::where('transaction_item_id', $ti->id)
                    ->where('direction', ReturnDirection::Returned->value)->sum('qty');
                $remaining = (int) $ti->qty - $already;
                if ($qty > $remaining) {
                    throw ValidationException::withMessages([
                        "returned_items.$i.qty" => "Qty retur melebihi sisa yang dapat diretur ({$remaining}).",
                    ]);
                }

                $price = (float) $ti->price_snapshot;
                $returnedTotal += $price * $qty;
                $resolvedReturned[] = [
                    'ti' => $ti,
                    'qty' => $qty,
                    'price' => $price,
                    'restock' => ! empty($r['restock']),
                ];
            }

            // --- Resolusi item penukaran ---
            $resolvedExchange = [];
            $exchangeTotal = 0;
            foreach ($exchangeInput as $i => $e) {
                $product = $locked[$e['product_id']] ?? null;
                if (! $product || ! $product->is_active) {
                    throw ValidationException::withMessages(["exchange_items.$i.product_id" => 'Produk tukar tidak tersedia.']);
                }
                $priceRow = $product->prices()->where('price_type', $e['price_type'])->first();
                if (! $priceRow) {
                    throw ValidationException::withMessages(["exchange_items.$i.price_type" => "Produk \"{$product->name}\" tidak memiliki harga untuk tipe tersebut."]);
                }
                $qty = (int) $e['qty'];
                if ($product->stock < $qty) {
                    throw ValidationException::withMessages(["exchange_items.$i.qty" => "Stok \"{$product->name}\" tidak mencukupi (tersisa {$product->stock})."]);
                }
                $price = (float) $priceRow->price;
                $exchangeTotal += $price * $qty;
                $resolvedExchange[] = [
                    'product' => $product,
                    'price_type' => $e['price_type'],
                    'qty' => $qty,
                    'price' => $price,
                ];
            }

            if (empty($resolvedReturned) && empty($resolvedExchange)) {
                throw ValidationException::withMessages(['returned_items' => 'Tidak ada item retur maupun penukaran.']);
            }

            // --- Saldo & pembayaran ---
            $balance = $exchangeTotal - $returnedTotal;
            $payment = (float) ($data['payment_amount'] ?? 0);
            $refund = 0;
            if ($balance > 0) {
                if ($payment < $balance) {
                    throw ValidationException::withMessages([
                        'payment_amount' => 'Pembayaran kurang dari selisih tagihan (' . number_format($balance, 0, ',', '.') . ').',
                    ]);
                }
            } elseif ($balance < 0) {
                $payment = 0;
                $refund = abs($balance);
            } else {
                $payment = 0;
            }

            $return = ReturnTransaction::create([
                'code' => $this->sequences->next(ReturnTransaction::class, 'code', 'RTN'),
                'transaction_id' => $trx->id,
                'kasir_id' => $kasir->id,
                'returned_total' => $returnedTotal,
                'exchange_total' => $exchangeTotal,
                'balance' => $balance,
                'payment_amount' => $payment,
                'refund_amount' => $refund,
                'note' => $data['note'] ?? null,
            ]);

            // Simpan item dikembalikan + restock (bila kondisi baik).
            foreach ($resolvedReturned as $r) {
                $ti = $r['ti'];
                $return->items()->create([
                    'direction' => ReturnDirection::Returned->value,
                    'product_id' => $ti->product_id,
                    'transaction_item_id' => $ti->id,
                    'item_name' => $ti->item_name,
                    'price_type_used' => $ti->price_type_used,
                    'price_snapshot' => $r['price'],
                    'qty' => $r['qty'],
                    'subtotal' => $r['price'] * $r['qty'],
                    'restock' => $r['restock'],
                ]);

                if ($r['restock'] && isset($locked[$ti->product_id])) {
                    $this->movements->apply($locked[$ti->product_id], StockMovementType::ReturnIn, $r['qty'], $return, $kasir, 'Retur ' . $return->code);
                }
            }

            // Simpan item penukaran + kurangi stok.
            foreach ($resolvedExchange as $e) {
                $return->items()->create([
                    'direction' => ReturnDirection::Exchange->value,
                    'product_id' => $e['product']->id,
                    'transaction_item_id' => null,
                    'item_name' => $e['product']->name,
                    'price_type_used' => $e['price_type'],
                    'price_snapshot' => $e['price'],
                    'qty' => $e['qty'],
                    'subtotal' => $e['price'] * $e['qty'],
                    'restock' => false,
                ]);

                $this->movements->apply($e['product'], StockMovementType::ReturnOut, -$e['qty'], $return, $kasir, 'Penukaran ' . $return->code);
            }

            return $return->load(['items', 'transaction', 'kasir']);
        });
    }
}
