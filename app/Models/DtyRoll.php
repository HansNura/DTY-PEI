<?php

namespace App\Models;

use App\Enums\RollStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
