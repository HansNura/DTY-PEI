<?php

namespace App\Http\Requests\Inventory;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class SaveDtyRollRequest extends FormRequest
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
            'denier' => ['required', 'integer', 'min:1'],
            'filamen' => ['required', 'integer', 'min:1'],
            'berat_gram' => ['required', 'numeric', 'gt:0', 'decimal:0,2'],
            'nomor_lot' => ['required', 'string', 'max:50'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'denier.required' => 'Denier wajib diisi.',
            'denier.integer' => 'Denier harus berupa bilangan bulat.',
            'denier.min' => 'Denier harus lebih besar dari 0.',
            'filamen.required' => 'Jumlah filamen wajib diisi.',
            'filamen.integer' => 'Jumlah filamen harus berupa bilangan bulat.',
            'filamen.min' => 'Jumlah filamen harus lebih besar dari 0.',
            'berat_gram.required' => 'Berat gulungan wajib diisi.',
            'berat_gram.numeric' => 'Berat gulungan harus berupa angka.',
            'berat_gram.gt' => 'Berat gulungan harus lebih besar dari 0.',
            'berat_gram.decimal' => 'Berat gulungan maksimal memiliki 2 angka desimal.',
            'nomor_lot.required' => 'Nomor lot wajib diisi.',
            'nomor_lot.string' => 'Nomor lot harus berupa teks.',
            'nomor_lot.max' => 'Nomor lot maksimal 50 karakter.',
        ];
    }
}
