<?php

namespace App\Services;

use App\Models\DeliveryOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeliveryReportService
{
    /**
     * @param  array{from?: ?string, to?: ?string, lot?: ?string, fleet_id?: ?int}  $filters
     * @return Collection<int, DeliveryOrder>
     */
    public function orders(array $filters): Collection
    {
        $lot = trim((string) ($filters['lot'] ?? ''));
        $items = function (HasMany $query) use ($lot): void {
            if ($lot !== '') {
                $query->whereHas('roll', fn (Builder $rollQuery) => $rollQuery->where('nomor_lot', $lot));
            }

            $query->with('roll');
        };

        return DeliveryOrder::query()
            ->with(['customer', 'fleet', 'driver', 'proofOfDelivery', 'items' => $items])
            ->when($filters['from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('tanggal', '>=', $date))
            ->when($filters['to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('tanggal', '<=', $date))
            ->when($filters['fleet_id'] ?? null, fn (Builder $query, int $fleetId) => $query->where('fleet_id', $fleetId))
            ->when($lot !== '', fn (Builder $query) => $query->whereHas(
                'items.roll',
                fn (Builder $rollQuery) => $rollQuery->where('nomor_lot', $lot)
            ))
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->get();
    }
}
