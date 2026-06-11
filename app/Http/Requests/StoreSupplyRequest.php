<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSupplyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'note' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.qty' => ['required', 'integer', 'min:1'],
            'items.*.purchase_price' => ['nullable', 'numeric', 'min:0'],
            'items.*.note' => ['nullable', 'string', 'max:500'],

            'items.*.prices' => ['nullable', 'array'],
            'items.*.prices.*.price_type' => ['required_with:items.*.prices', 'string', 'exists:price_types,code'],
            'items.*.prices.*.price' => ['required_with:items.*.prices', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'supplier_id.required' => 'Pemasok wajib dipilih.',
            'items.required' => 'Minimal satu produk harus disupply.',
            'items.*.product_id.required' => 'Produk wajib dipilih.',
            'items.*.qty.required' => 'Jumlah wajib diisi.',
            'items.*.qty.min' => 'Jumlah minimal 1.',
        ];
    }
}
