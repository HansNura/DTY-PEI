<?php

namespace App\Http\Requests\Inventory;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdatePackedCartonRequest extends FormRequest
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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $carton = $this->route('carton');

            if ($carton && (float) $this->input('gross_weight_kg') <= (float) $carton->net_weight_kg) {
                $validator->errors()->add(
                    'gross_weight_kg',
                    'Gross weight harus lebih besar dari net weight kardus.',
                );
            }
        });
    }
}
