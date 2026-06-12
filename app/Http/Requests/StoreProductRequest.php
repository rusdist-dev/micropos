<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Otorisasi ditangani middleware permission di route.
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('products', 'sku')],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'brand' => ['nullable', 'string', 'max:255'],
            'stock' => ['required', 'integer', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:2048'],

            'default_type' => ['required', 'string', Rule::exists('price_types', 'code')],
            'prices' => ['required', 'array', 'min:1'],
            'prices.*.price_type' => ['required', 'string', Rule::exists('price_types', 'code')],
            'prices.*.price' => ['required', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $types = collect($this->input('prices', []))->pluck('price_type')->all();

            if ($this->filled('default_type') && ! in_array($this->input('default_type'), $types, true)) {
                $validator->errors()->add('default_type', 'Tipe harga default harus termasuk salah satu harga yang diisi.');
            }

            if (count($types) !== count(array_unique($types))) {
                $validator->errors()->add('prices', 'Tipe harga tidak boleh duplikat.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama produk wajib diisi.',
            'sku.unique' => 'SKU sudah digunakan produk lain.',
            'stock.required' => 'Stok wajib diisi.',
            'stock.integer' => 'Stok harus berupa angka.',
            'prices.required' => 'Minimal satu harga wajib diisi.',
            'prices.*.price_type.exists' => 'Tipe harga tidak valid.',
            'prices.*.price.numeric' => 'Harga harus berupa angka.',
            'default_type.required' => 'Tipe harga default wajib dipilih.',
        ];
    }
}
