<?php

namespace App\Http\Requests;

/** Aturan edit transaksi sama dengan pembuatan; otorisasi dicek lewat middleware permission. */
class UpdateTransactionRequest extends StoreTransactionRequest
{
}
