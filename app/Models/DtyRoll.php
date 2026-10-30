<?php

namespace App\Models;

use App\Enums\RollStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DtyRoll extends Model
{
    use HasFactory;

    protected $fillable = [
        'kode_rol',
        'denier',
        'filamen',
        'berat_gram',
        'nomor_lot',
        'qr_token',
        'status',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'berat_gram' => 'decimal:2',
            'status' => RollStatus::class,
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function deliveryOrderItem(): HasOne
    {
        return $this->hasOne(DeliveryOrderItem::class);
    }

    public function loadScans(): HasMany
    {
        return $this->hasMany(LoadScan::class);
    }
}
