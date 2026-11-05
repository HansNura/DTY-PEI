<?php

namespace App\Http\Requests\Logistics;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

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
            'email' => ['nullable', 'email', 'max:150'],
            'npwp' => ['nullable', 'string', 'max:30'],
            'alamat' => ['required', 'string'],
            'kota' => ['nullable', 'string', 'max:100'],
            'provinsi' => ['nullable', 'string', 'max:100'],
            'kode_pos' => ['nullable', 'string', 'max:10'],
            'kontak_nama' => ['required', 'string', 'max:100'],
            'kontak_jabatan' => ['nullable', 'string', 'max:100'],
            'kontak_telepon' => ['required', 'string', 'max:30'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90', 'decimal:0,7'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180', 'decimal:0,7'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => $this->filled('email') ? Str::lower(trim((string) $this->input('email'))) : null,
            'npwp' => $this->filled('npwp') ? trim((string) $this->input('npwp')) : null,
            'kota' => $this->filled('kota') ? trim((string) $this->input('kota')) : null,
            'provinsi' => $this->filled('provinsi') ? trim((string) $this->input('provinsi')) : null,
            'kode_pos' => $this->filled('kode_pos') ? trim((string) $this->input('kode_pos')) : null,
        ]);
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
            'email.email' => 'Format email tidak valid.',
            'email.max' => 'Email maksimal 150 karakter.',
            'npwp.max' => 'NPWP maksimal 30 karakter.',
            'alamat.required' => 'Alamat wajib diisi.',
            'alamat.string' => 'Alamat harus berupa teks.',
            'kota.max' => 'Kota maksimal 100 karakter.',
            'provinsi.max' => 'Provinsi maksimal 100 karakter.',
            'kode_pos.max' => 'Kode pos maksimal 10 karakter.',
            'kontak_nama.required' => 'Nama kontak penerima wajib diisi.',
            'kontak_nama.string' => 'Nama kontak penerima harus berupa teks.',
            'kontak_nama.max' => 'Nama kontak penerima maksimal 100 karakter.',
            'kontak_jabatan.max' => 'Jabatan kontak maksimal 100 karakter.',
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
            'catatan.max' => 'Catatan maksimal 1000 karakter.',
        ];
    }
}
