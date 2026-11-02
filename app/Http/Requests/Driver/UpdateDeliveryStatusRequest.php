<?php

namespace App\Http\Requests\Driver;

use App\Enums\TrackingStatus;
use App\Enums\UserRole;
use App\Models\DeliveryOrder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeliveryStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $order = $this->route('order');
        $user = $this->user();

        return $user !== null
            && $user->hasRole(UserRole::Driver)
            && $order instanceof DeliveryOrder
            && $order->driver_id === $user->id;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(TrackingStatus::class)],
            'latitude' => ['nullable', 'numeric', 'between:-90,90', 'decimal:0,7', 'required_with:longitude'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180', 'decimal:0,7', 'required_with:latitude'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Status perjalanan wajib dipilih.',
            'status.enum' => 'Status perjalanan tidak valid.',
            'latitude.numeric' => 'Koordinat lintang tidak valid.',
            'latitude.between' => 'Koordinat lintang harus antara -90 dan 90.',
            'latitude.decimal' => 'Koordinat lintang maksimal 7 angka desimal.',
            'latitude.required_with' => 'Lintang dan bujur harus dikirim bersamaan.',
            'longitude.numeric' => 'Koordinat bujur tidak valid.',
            'longitude.between' => 'Koordinat bujur harus antara -180 dan 180.',
            'longitude.decimal' => 'Koordinat bujur maksimal 7 angka desimal.',
            'longitude.required_with' => 'Lintang dan bujur harus dikirim bersamaan.',
        ];
    }
}
