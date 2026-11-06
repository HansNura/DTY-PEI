<?php

namespace App\Http\Requests\Inventory;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SaveProductionBatchRequest extends FormRequest
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
            'nomor_merge' => [
                'required',
                'string',
                'max:50',
                Rule::unique('production_batches', 'nomor_merge')->ignore($this->route('batch')),
            ],
            'denier' => ['required', 'integer', 'min:1'],
            'filamen' => ['required', 'integer', 'min:1'],
            'intermingled' => ['sometimes', 'boolean'],
            'twist_direction' => ['nullable', Rule::in(['Z', 'S'])],
            'production_date' => ['required', 'date'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'nomor_merge' => Str::upper(trim((string) $this->input('nomor_merge'))),
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
            'nomor_merge.required' => 'Nomor merge wajib diisi.',
            'nomor_merge.unique' => 'Nomor merge sudah digunakan batch lain.',
            'denier.required' => 'Denier wajib diisi.',
            'filamen.required' => 'Jumlah filamen wajib diisi.',
            'production_date.required' => 'Tanggal produksi wajib diisi.',
            'twist_direction.in' => 'Arah twist harus Z atau S.',
        ];
    }
}
