<?php

namespace App\Http\Requests\Inventory;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WeighDtyRollRequest extends FormRequest
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
            'berat_gram' => ['required', 'numeric', 'gt:0', 'decimal:0,2', 'max:99999'],
            'nomor_seri' => [
                'required',
                'digits:17',
                Rule::unique('dty_rolls', 'nomor_seri')->ignore($this->route('roll')),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'berat_gram.required' => 'Berat bobbin wajib diisi.',
            'berat_gram.numeric' => 'Berat bobbin harus berupa angka.',
            'berat_gram.gt' => 'Berat bobbin harus lebih besar dari 0.',
            'berat_gram.decimal' => 'Berat bobbin maksimal memiliki 2 angka desimal.',
            'berat_gram.max' => 'Berat bobbin tidak wajar. Periksa kembali timbangan.',
            'nomor_seri.required' => 'Nomor seri wajib diisi.',
            'nomor_seri.digits' => 'Nomor seri harus 17 digit angka.',
            'nomor_seri.unique' => 'Nomor seri sudah digunakan bobbin lain.',
        ];
    }
}
