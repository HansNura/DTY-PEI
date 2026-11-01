<?php

namespace App\Http\Requests\Logistics;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeliveryReportRequest extends FormRequest
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
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'lot' => ['nullable', 'string', 'max:50'],
            'fleet_id' => ['nullable', 'integer', Rule::exists('fleets', 'id')],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'from.date' => 'Tanggal awal tidak valid.',
            'to.date' => 'Tanggal akhir tidak valid.',
            'to.after_or_equal' => 'Tanggal akhir tidak boleh sebelum tanggal awal.',
            'lot.max' => 'Nomor lot maksimal 50 karakter.',
            'fleet_id.exists' => 'Armada yang dipilih tidak ditemukan.',
        ];
    }
}
