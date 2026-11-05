<?php

namespace App\Models;

use App\Enums\RollStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DtyCarton extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor_hu',
        'nomor_merge',
        'grade',
        'production_date',
        'denier',
        'filamen',
        'intermingled',
        'twist_direction',
        'gross_weight_kg',
        'net_weight_kg',
        'unit_bobbins',
        'pallet_number',
        'qr_token',
        'catatan',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'production_date' => 'date',
            'intermingled' => 'boolean',
            'gross_weight_kg' => 'decimal:2',
            'net_weight_kg' => 'decimal:2',
            'unit_bobbins' => 'integer',
        ];
    }

    public function rolls(): HasMany
    {
        return $this->hasMany(DtyRoll::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getSpecificationAttribute(): string
    {
        $intermingled = $this->intermingled ? 'I' : 'NI';
        $twist = $this->twist_direction ? ' '.$this->twist_direction : '';

        return "{$this->denier}/{$this->filamen} {$intermingled}{$twist}";
    }

    public function getNetWeightPerBobbinGramAttribute(): float
    {
        if ($this->unit_bobbins < 1) {
            return 0.0;
        }

        return round(((float) $this->net_weight_kg * 1000) / $this->unit_bobbins, 2);
    }

    public function getStatusAttribute(): RollStatus
    {
        $statuses = $this->rolls->pluck('status');

        if ($statuses->isEmpty()) {
            return RollStatus::InStock;
        }

        if ($statuses->every(fn (RollStatus $status): bool => $status === RollStatus::Cancelled)) {
            return RollStatus::Cancelled;
        }

        if ($statuses->every(fn (RollStatus $status): bool => $status === RollStatus::Delivered)) {
            return RollStatus::Delivered;
        }

        if ($statuses->contains(RollStatus::Loaded)) {
            return RollStatus::Loaded;
        }

        if ($statuses->contains(RollStatus::Allocated)) {
            return RollStatus::Allocated;
        }

        return RollStatus::InStock;
    }
}
