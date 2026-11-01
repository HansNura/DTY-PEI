<?php

namespace App\Http\Requests\Driver;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\ValidationException;

class ConfirmDeliveryOrderRequest extends FormRequest
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
            'received_by_name' => ['required', 'string', 'max:100'],
            'scanned_tokens' => ['required', 'array', 'min:1', 'max:500'],
            'scanned_tokens.*' => ['required', 'string', 'max:128'],
            'signature' => [
                'required',
                'string',
                'max:400000',
                'regex:/\Adata:image\/png;base64,[A-Za-z0-9+\/]+=*\z/',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'received_by_name.required' => 'Nama penerima wajib diisi.',
            'received_by_name.max' => 'Nama penerima maksimal 100 karakter.',
            'scanned_tokens.required' => 'Pindai semua QR gulungan sebelum konfirmasi.',
            'scanned_tokens.min' => 'Pindai semua QR gulungan sebelum konfirmasi.',
            'scanned_tokens.max' => 'Jumlah scan melebihi batas untuk satu surat jalan.',
            'scanned_tokens.*.string' => 'Data QR tidak valid.',
            'signature.required' => 'Tanda tangan penerima wajib diisi.',
            'signature.max' => 'Ukuran tanda tangan terlalu besar. Silakan tanda tangani ulang.',
            'signature.regex' => 'Format tanda tangan tidak valid.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $response = redirect($this->getRedirectUrl())
            ->withInput($this->except('signature'))
            ->withErrors($validator, $this->errorBag);

        throw new ValidationException($validator, $response, $this->errorBag);
    }
}
