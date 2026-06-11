<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockOpnameCountsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer'],
            'items.*.counted_qty' => ['nullable', 'integer', 'min:0'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Tidak ada data hitungan untuk disimpan.',
            'items.*.counted_qty.min' => 'Jumlah hitung tidak boleh negatif.',
        ];
    }
}
