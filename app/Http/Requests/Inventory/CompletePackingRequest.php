<?php

namespace App\Http\Requests\Inventory;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class CompletePackingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::AdminGudang) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'gross_weight_kg' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'pallet_number' => ['nullable', 'string', 'max:50'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'pallet_number' => $this->filled('pallet_number')
                ? strtoupper(trim((string) $this->input('pallet_number')))
                : null,
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'gross_weight_kg.required' => 'Gross weight kardus wajib diisi.',
            'gross_weight_kg.gt' => 'Gross weight harus lebih besar dari 0.',
            'gross_weight_kg.decimal' => 'Gross weight maksimal memiliki 2 angka desimal.',
            'pallet_number.max' => 'Nomor pallet maksimal 50 karakter.',
        ];
    }
}
