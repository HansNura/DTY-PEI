<?php

namespace App\Models;

use App\Enums\DeliveryOrderStatus;
use App\Enums\RollStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class DeliveryOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor_do',
        'customer_id',
        'fleet_id',
        'driver_id',
        'tanggal',
        'status',
        'catatan',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => DeliveryOrderStatus::class,
            'tanggal' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function fleet(): BelongsTo
    {
        return $this->belongsTo(Fleet::class);
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(DeliveryOrderItem::class);
    }

    public function rolls(): HasManyThrough
    {
        return $this->hasManyThrough(
            DtyRoll::class,
            DeliveryOrderItem::class,
            'delivery_order_id',
            'id',
            'id',
            'dty_roll_id'
        );
    }

    public function loadScans(): HasMany
    {
        return $this->hasMany(LoadScan::class);
    }

    public function getTotalBeratKgAttribute(): float
    {
        return (float) $this->items->sum('berat_kg');
    }

    public function getTotalRolAttribute(): int
    {
        return $this->items->count();
    }

    public function getLoadedCountAttribute(): int
    {
        return $this->rolls->filter(fn (DtyRoll $roll) => $roll->status === RollStatus::Loaded)->count();
    }
}
