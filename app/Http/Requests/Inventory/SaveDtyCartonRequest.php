<?php

namespace App\Http\Requests\Inventory;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SaveDtyCartonRequest extends FormRequest
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
            'nomor_hu' => [
                'required',
                'string',
                'max:50',
                Rule::unique('dty_cartons', 'nomor_hu')->ignore($this->route('carton')),
            ],
            'nomor_merge' => ['required', 'string', 'max:50'],
            'grade' => ['required', 'string', 'max:10'],
            'production_date' => ['required', 'date'],
            'denier' => ['required', 'integer', 'min:1'],
            'filamen' => ['required', 'integer', 'min:1'],
            'intermingled' => ['sometimes', 'boolean'],
            'twist_direction' => ['nullable', Rule::in(['Z', 'S'])],
            'gross_weight_kg' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'net_weight_kg' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'lte:gross_weight_kg'],
            'unit_bobbins' => ['required', 'integer', 'min:1', 'max:100'],
            'pallet_number' => ['nullable', 'string', 'max:50'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nomor_hu' => Str::upper(trim((string) $this->input('nomor_hu'))),
            'nomor_merge' => Str::upper(trim((string) $this->input('nomor_merge'))),
            'grade' => Str::upper(trim((string) $this->input('grade'))),
            'pallet_number' => $this->filled('pallet_number') ? Str::upper(trim((string) $this->input('pallet_number'))) : null,
            'twist_direction' => $this->filled('twist_direction') ? Str::upper((string) $this->input('twist_direction')) : null,
            'intermingled' => $this->boolean('intermingled'),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nomor_hu.required' => 'Nomor HU / karton wajib diisi.',
            'nomor_hu.unique' => 'Nomor HU sudah terdaftar pada kardus lain.',
            'nomor_merge.required' => 'Nomor lot / merge wajib diisi.',
            'grade.required' => 'Grade wajib diisi.',
            'production_date.required' => 'Tanggal produksi wajib diisi.',
            'denier.required' => 'Denier wajib diisi.',
            'filamen.required' => 'Jumlah filamen wajib diisi.',
            'gross_weight_kg.required' => 'Gross weight wajib diisi.',
            'gross_weight_kg.gt' => 'Gross weight harus lebih besar dari 0.',
            'net_weight_kg.required' => 'Net weight wajib diisi.',
            'net_weight_kg.lte' => 'Net weight tidak boleh lebih besar dari gross weight.',
            'unit_bobbins.required' => 'Jumlah bobbin wajib diisi.',
            'unit_bobbins.min' => 'Jumlah bobbin minimal 1.',
            'unit_bobbins.max' => 'Jumlah bobbin maksimal 100.',
            'twist_direction.in' => 'Arah twist harus Z atau S.',
            'pallet_number.max' => 'Nomor pallet maksimal 50 karakter.',
        ];
    }
}
