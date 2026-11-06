<?php

namespace App\Models;

use App\Enums\RollStatus;
use App\Support\BobbinWeightStandard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class DtyRoll extends Model
{
    use HasFactory;

    protected $fillable = [
        'dty_carton_id',
        'production_batch_id',
        'kode_rol',
        'nomor_seri',
        'grade',
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

    public function carton(): BelongsTo
    {
        return $this->belongsTo(DtyCarton::class, 'dty_carton_id');
    }

    public function productionBatch(): BelongsTo
    {
        return $this->belongsTo(ProductionBatch::class);
    }

    public function deliveryOrderItem(): HasOne
    {
        return $this->hasOne(DeliveryOrderItem::class);
    }

    public function loadScans(): HasMany
    {
        return $this->hasMany(LoadScan::class);
    }

    public function weightInKg(): string
    {
        $hundredthsOfKg = intdiv((int) str_replace('.', '', (string) $this->berat_gram) + 500, 1000);

        return intdiv($hundredthsOfKg, 100).'.'.str_pad((string) ($hundredthsOfKg % 100), 2, '0', STR_PAD_LEFT);
    }

    public function getSpecificationAttribute(): string
    {
        if ($this->carton) {
            return 'DTY '.$this->carton->specification;
        }

        if ($this->productionBatch) {
            return 'DTY '.$this->productionBatch->specification;
        }

        return "DTY {$this->denier}/{$this->filamen} NI";
    }

    public function getBobbinWeightGradeAttribute(): ?string
    {
        return $this->grade ?: BobbinWeightStandard::gradeFor((float) $this->berat_gram);
    }
}
