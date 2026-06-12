<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'store_name' => ['required', 'string', 'max:255'],
            'store_address' => ['nullable', 'string', 'max:500'],
            'store_phone' => ['nullable', 'string', 'max:50'],
            'receipt_footer' => ['nullable', 'string', 'max:500'],
            'primary_color' => ['nullable', 'string', 'regex:/^#?[0-9a-fA-F]{6}$/'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
            'remove_logo' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'store_name.required' => 'Nama toko wajib diisi.',
            'primary_color.regex' => 'Warna harus berupa kode heksadesimal, mis. #14b8a6.',
            'logo.image' => 'Logo harus berupa berkas gambar.',
            'logo.mimes' => 'Format logo: jpg, jpeg, png, webp, atau svg.',
            'logo.max' => 'Ukuran logo maksimal 2 MB.',
        ];
    }
}
