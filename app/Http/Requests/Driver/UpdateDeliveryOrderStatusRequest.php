<?php

namespace App\Http\Requests\Driver;

use App\Enums\DeliveryOrderStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeliveryOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole(UserRole::Driver) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                Rule::in([
                    DeliveryOrderStatus::Departed->value,
                    DeliveryOrderStatus::InTransit->value,
                    DeliveryOrderStatus::Arrived->value,
                ]),
            ],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'decimal:0,7'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'decimal:0,7'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Status perjalanan wajib dipilih.',
            'status.in' => 'Status perjalanan tidak valid.',
            'latitude.numeric' => 'Latitude harus berupa angka.',
            'latitude.between' => 'Latitude harus berada di antara -90 dan 90.',
            'latitude.decimal' => 'Latitude maksimal memiliki 7 angka desimal.',
            'longitude.numeric' => 'Longitude harus berupa angka.',
            'longitude.between' => 'Longitude harus berada di antara -180 dan 180.',
            'longitude.decimal' => 'Longitude maksimal memiliki 7 angka desimal.',
        ];
    }
}
