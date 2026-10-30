<?php

namespace App\Models;

use App\Enums\LoadScanResult;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoadScan extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_order_id',
        'dty_roll_id',
        'scanned_by',
        'result',
        'scanned_at',
    ];

    protected function casts(): array
    {
        return [
            'result' => LoadScanResult::class,
            'scanned_at' => 'datetime',
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

    public function scanner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }
}
