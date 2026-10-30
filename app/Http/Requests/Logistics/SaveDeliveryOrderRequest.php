<?php

namespace App\Http\Requests\Logistics;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveDeliveryOrderRequest extends FormRequest
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
            'customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'fleet_id' => [
                'required',
                'integer',
                Rule::exists('fleets', 'id')->where(fn ($query) => $query->where('is_active', true)),
            ],
            'driver_id' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query
                    ->where('role', UserRole::Driver->value)
                    ->where('is_active', true)),
            ],
            'tanggal' => ['required', 'date'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'rolls' => ['required', 'array', 'min:1'],
            'rolls.*' => [
                'required',
                'integer',
                Rule::exists('dty_rolls', 'id'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'customer_id.required' => 'Pabrik pelanggan tujuan wajib dipilih.',
            'customer_id.exists' => 'Pelanggan yang dipilih tidak valid atau sudah nonaktif.',
            'fleet_id.required' => 'Armada kendaraan wajib dipilih.',
            'fleet_id.exists' => 'Armada yang dipilih tidak valid atau sudah nonaktif.',
            'driver_id.required' => 'Pengemudi / driver wajib dipilih.',
            'driver_id.exists' => 'Driver yang dipilih tidak valid atau sudah nonaktif.',
            'tanggal.required' => 'Tanggal pengiriman wajib diisi.',
            'tanggal.date' => 'Format tanggal pengiriman tidak valid.',
            'catatan.max' => 'Catatan maksimal 1000 karakter.',
            'rolls.required' => 'Pilih minimal satu gulungan DTY untuk dialokasikan.',
            'rolls.array' => 'Data gulungan DTY harus berupa daftar.',
            'rolls.min' => 'Pilih minimal satu gulungan DTY untuk dialokasikan.',
            'rolls.*.exists' => 'Satu atau lebih gulungan DTY yang dipilih tidak valid.',
        ];
    }
}
