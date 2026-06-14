<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateServiceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'technician_id' => ['nullable', 'integer', 'exists:technicians,id'],
            'due_date' => ['nullable', 'date'],
            'discount' => ['nullable', 'numeric', 'min:0'],
            'note' => ['nullable', 'string'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.item_type' => ['required', 'in:product,service'],
            'items.*.qty' => ['required', 'integer', 'min:1'],

            'items.*.product_id' => ['required_if:items.*.item_type,product', 'nullable', 'integer', 'exists:products,id'],

            'items.*.service_id' => ['nullable', 'integer', 'exists:services,id'],
            'items.*.item_name' => ['required_if:items.*.item_type,service', 'nullable', 'string', 'max:255'],
            'items.*.price' => ['required_if:items.*.item_type,service', 'nullable', 'numeric', 'min:0'],
            'items.*.note' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Order servis harus memiliki minimal satu item.',
            'items.*.product_id.required_if' => 'Produk wajib dipilih untuk item produk.',
            'items.*.item_name.required_if' => 'Nama jasa wajib diisi.',
            'items.*.price.required_if' => 'Harga jasa wajib diisi.',
        ];
    }
}
