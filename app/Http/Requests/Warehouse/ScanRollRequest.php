<?php

namespace App\Http\Requests\Warehouse;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class ScanRollRequest extends FormRequest
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
            'qr_token' => ['required', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'qr_token.required' => 'Token QR atau kode gulungan wajib diisi.',
            'qr_token.string' => 'Format token QR tidak valid.',
        ];
    }
}
