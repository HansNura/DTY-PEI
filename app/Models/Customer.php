<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_pelanggan',
        'nama_pabrik',
        'email',
        'npwp',
        'alamat',
        'kota',
        'provinsi',
        'kode_pos',
        'kontak_nama',
        'kontak_jabatan',
        'kontak_telepon',
        'latitude',
        'longitude',
        'catatan',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_active' => 'boolean',
        ];
    }

    public function deliveryOrders(): HasMany
    {
        return $this->hasMany(DeliveryOrder::class);
    }
}
