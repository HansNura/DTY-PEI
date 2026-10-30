<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_order_id',
        'dty_roll_id',
        'berat_kg',
    ];

    protected function casts(): array
    {
        return [
            'berat_kg' => 'decimal:2',
        ];
    }

    public function deliveryOrder(): BelongsTo
    {
        return $this->belongsTo(DeliveryOrder::class);
    }

    public function dtyRoll(): BelongsTo
    {
        return $this->belongsTo(DtyRoll::class);
    }
}
