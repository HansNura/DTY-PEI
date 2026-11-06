<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionBatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'nomor_merge',
        'denier',
        'filamen',
        'intermingled',
        'twist_direction',
        'production_date',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'production_date' => 'date',
            'intermingled' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive(Builder $query): void
    {
        $query->where('is_active', true);
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
}
