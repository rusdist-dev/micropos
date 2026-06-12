<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product');

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:50', Rule::unique('products', 'sku')->ignore($productId)],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'brand' => ['nullable', 'string', 'max:255'],
            'stock' => ['sometimes', 'required', 'integer', 'min:0'],
            'min_stock' => ['nullable', 'integer', 'min:0'],
            'purchase_price' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'image' => ['nullable', 'image', 'max:2048'],

            'default_type' => ['sometimes', 'required', 'string', Rule::exists('price_types', 'code')],
            'prices' => ['sometimes', 'required', 'array', 'min:1'],
            'prices.*.price_type' => ['required_with:prices', 'string', Rule::exists('price_types', 'code')],
            'prices.*.price' => ['required_with:prices', 'numeric', 'min:0'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (! $this->has('prices')) {
                return;
            }

            $types = collect($this->input('prices', []))->pluck('price_type')->all();

            if ($this->filled('default_type') && ! in_array($this->input('default_type'), $types, true)) {
                $validator->errors()->add('default_type', 'Tipe harga default harus termasuk salah satu harga yang diisi.');
            }

            if (count($types) !== count(array_unique($types))) {
                $validator->errors()->add('prices', 'Tipe harga tidak boleh duplikat.');
            }
        });
    }
}
