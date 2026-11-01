<?php

namespace App\Models;

use App\Enums\DeliveryOrderStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryTrackingEvent extends Model
{
    protected $fillable = [
        'delivery_order_id',
        'status',
        'latitude',
        'longitude',
        'recorded_by',
        'recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => DeliveryOrderStatus::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'recorded_at' => 'datetime',
        ];
    }

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
