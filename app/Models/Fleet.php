<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Fleet extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor_polisi',
        'jenis_kendaraan',
        'kapasitas_kg',
        'driver_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'kapasitas_kg' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }
}
