<?php

namespace App\Http\Requests\Logistics;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveFleetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::Logistik) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'nomor_polisi' => [
                'required',
                'string',
                'max:20',
                Rule::unique('fleets', 'nomor_polisi')->ignore($this->route('fleet')),
            ],
            'jenis_kendaraan' => ['required', 'string', 'max:50'],
            'kapasitas_kg' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'driver_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('role', UserRole::Driver->value)
                    ->where('is_active', true)),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nomor_polisi.required' => 'Nomor polisi wajib diisi.',
            'nomor_polisi.string' => 'Nomor polisi harus berupa teks.',
            'nomor_polisi.max' => 'Nomor polisi maksimal 20 karakter.',
            'nomor_polisi.unique' => 'Nomor polisi sudah terdaftar.',
            'jenis_kendaraan.required' => 'Jenis kendaraan wajib diisi.',
            'jenis_kendaraan.string' => 'Jenis kendaraan harus berupa teks.',
            'jenis_kendaraan.max' => 'Jenis kendaraan maksimal 50 karakter.',
            'kapasitas_kg.required' => 'Kapasitas kendaraan wajib diisi.',
            'kapasitas_kg.numeric' => 'Kapasitas kendaraan harus berupa angka.',
            'kapasitas_kg.gt' => 'Kapasitas kendaraan harus lebih besar dari 0.',
            'kapasitas_kg.decimal' => 'Kapasitas kendaraan maksimal memiliki 2 angka desimal.',
            'driver_id.integer' => 'Pengemudi yang dipilih tidak valid.',
            'driver_id.exists' => 'Pengemudi harus merupakan akun driver yang aktif.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $plate = $this->input('nomor_polisi');

        $this->merge([
            'nomor_polisi' => is_string($plate) ? strtoupper(trim($plate)) : $plate,
        ]);
    }
}
