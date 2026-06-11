<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreReturnRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'transaction_id' => ['required', 'integer', 'exists:transactions,id'],
            'payment_amount' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],

            'returned_items' => ['nullable', 'array'],
            'returned_items.*.transaction_item_id' => ['required', 'integer', 'exists:transaction_items,id'],
            'returned_items.*.qty' => ['required', 'integer', 'min:1'],
            'returned_items.*.restock' => ['boolean'],

            'exchange_items' => ['nullable', 'array'],
            'exchange_items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'exchange_items.*.price_type' => ['required', 'string', 'exists:price_types,code'],
            'exchange_items.*.qty' => ['required', 'integer', 'min:1'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $returned = $this->input('returned_items', []);
            $exchange = $this->input('exchange_items', []);
            if (empty($returned) && empty($exchange)) {
                $validator->errors()->add('returned_items', 'Minimal satu item retur atau penukaran harus diisi.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'transaction_id.required' => 'Transaksi asal wajib dipilih.',
            'transaction_id.exists' => 'Transaksi asal tidak ditemukan.',
        ];
    }
}
