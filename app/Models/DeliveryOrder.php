<?php

namespace App\Models;

use App\Enums\DeliveryOrderStatus;
use App\Enums\RollStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Collection;

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

    public function trackingEvents(): HasMany
    {
        return $this->hasMany(DeliveryTrackingEvent::class)->orderBy('recorded_at');
    }

    public function latestTrackingEvent(): HasOne
    {
        return $this->hasOne(DeliveryTrackingEvent::class)->latestOfMany('recorded_at');
    }

    public function proofOfDelivery(): HasOne
    {
        return $this->hasOne(ProofOfDelivery::class);
    }

    public function receiptScans(): HasMany
    {
        return $this->hasMany(DeliveryOrderReceiptScan::class);
    }

    public function totalWeightInKg(): string
    {
        $hundredthsOfKg = $this->items->sum(
            fn (DeliveryOrderItem $item): int => (int) str_replace('.', '', (string) $item->berat_kg)
        );

        return intdiv($hundredthsOfKg, 100).'.'.str_pad((string) ($hundredthsOfKg % 100), 2, '0', STR_PAD_LEFT);
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
        return $this->rolls
            ->filter(fn (DtyRoll $roll) => in_array($roll->status, [RollStatus::Loaded, RollStatus::Delivered], true))
            ->count();
    }

    public function getTotalBoxAttribute(): int
    {
        return $this->items
            ->filter(fn (DeliveryOrderItem $item): bool => $item->dtyRoll?->carton !== null)
            ->map(fn (DeliveryOrderItem $item): int => $item->dtyRoll->carton->id)
            ->unique()
            ->count();
    }

    public function getLoadedBoxCountAttribute(): int
    {
        return $this->items
            ->filter(fn (DeliveryOrderItem $item): bool => $item->dtyRoll?->carton !== null)
            ->groupBy(fn (DeliveryOrderItem $item): int => $item->dtyRoll->carton->id)
            ->filter(fn ($items): bool => $items->every(
                fn (DeliveryOrderItem $item): bool => in_array($item->dtyRoll->status, [RollStatus::Loaded, RollStatus::Delivered], true)
            ))
            ->count();
    }

    /**
     * Palletisasi otomatis per 30 kardus pada saat DO diterbitkan.
     *
     * @return array<int, array{number: string, cartons: Collection, box_count: int, bobbin_count: int, net_kg: float}>
     */
    public function palletGroups(): array
    {
        return $this->cartonGroups()
            ->chunk(DtyCarton::PALLET_BOX_CAPACITY)
            ->values()
            ->map(fn ($chunk, int $index): array => [
                'number' => 'P'.str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'cartons' => $chunk,
                'box_count' => $chunk->count(),
                'bobbin_count' => (int) $chunk->sum('bobbin_count'),
                'net_kg' => round((float) $chunk->sum('net_kg'), 2),
            ])
            ->all();
    }

    /**
     * @return Collection<int, array{carton: DtyCarton, net_kg: float, bobbin_count: int}>
     */
    private function cartonGroups(): Collection
    {
        return $this->items
            ->filter(fn (DeliveryOrderItem $item): bool => $item->dtyRoll?->carton !== null)
            ->groupBy(fn (DeliveryOrderItem $item): int => $item->dtyRoll->carton->id)
            ->map(function ($items): array {
                $carton = $items->first()->dtyRoll->carton;

                return [
                    'carton' => $carton,
                    'net_kg' => round($items->sum(fn (DeliveryOrderItem $item): float => (float) $item->berat_kg), 2),
                    'bobbin_count' => $items->count(),
                ];
            })
            ->sortBy(fn (array $group): string => $group['carton']->nomor_hu)
            ->values();
    }
}
