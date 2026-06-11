<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class StorePriceTypeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Auto-generate kode dari nama bila tidak dikirim.
        if (! $this->filled('code') && $this->filled('name')) {
            $this->merge(['code' => Str::slug($this->input('name'))]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('price_types', 'code')],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama tipe harga wajib diisi.',
            'code.required' => 'Kode tipe harga wajib diisi.',
            'code.unique' => 'Kode tipe harga sudah digunakan.',
            'code.alpha_dash' => 'Kode hanya boleh huruf, angka, dan tanda hubung.',
        ];
    }
}
