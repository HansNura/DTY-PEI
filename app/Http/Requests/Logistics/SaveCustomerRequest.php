<?php

namespace App\Http\Requests\Logistics;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;

class SaveCustomerRequest extends FormRequest
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
            'nama_pabrik' => ['required', 'string', 'max:150'],
            'alamat' => ['required', 'string'],
            'kontak_nama' => ['required', 'string', 'max:100'],
            'kontak_telepon' => ['required', 'string', 'max:30'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90', 'decimal:0,7'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180', 'decimal:0,7'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_pabrik.required' => 'Nama pabrik wajib diisi.',
            'nama_pabrik.string' => 'Nama pabrik harus berupa teks.',
            'nama_pabrik.max' => 'Nama pabrik maksimal 150 karakter.',
            'alamat.required' => 'Alamat wajib diisi.',
            'alamat.string' => 'Alamat harus berupa teks.',
            'kontak_nama.required' => 'Nama kontak penerima wajib diisi.',
            'kontak_nama.string' => 'Nama kontak penerima harus berupa teks.',
            'kontak_nama.max' => 'Nama kontak penerima maksimal 100 karakter.',
            'kontak_telepon.required' => 'Nomor telepon penerima wajib diisi.',
            'kontak_telepon.string' => 'Nomor telepon penerima harus berupa teks.',
            'kontak_telepon.max' => 'Nomor telepon penerima maksimal 30 karakter.',
            'latitude.required_with' => 'Latitude dan longitude harus diisi berpasangan.',
            'latitude.numeric' => 'Latitude harus berupa angka.',
            'latitude.between' => 'Latitude harus berada di antara -90 dan 90.',
            'latitude.decimal' => 'Latitude maksimal memiliki 7 angka desimal.',
            'longitude.required_with' => 'Latitude dan longitude harus diisi berpasangan.',
            'longitude.numeric' => 'Longitude harus berupa angka.',
            'longitude.between' => 'Longitude harus berada di antara -180 dan 180.',
            'longitude.decimal' => 'Longitude maksimal memiliki 7 angka desimal.',
        ];
    }
}
