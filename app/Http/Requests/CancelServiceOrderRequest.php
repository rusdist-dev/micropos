<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelServiceOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cancel_note' => ['required', 'string', 'max:1000'],
            // Biaya pembatalan (DP ditahan). Null = tahan seluruh DP. Di-clamp ke <= DP di service.
            'cancellation_fee' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'cancel_note.required' => 'Keterangan pembatalan wajib diisi.',
        ];
    }
}
