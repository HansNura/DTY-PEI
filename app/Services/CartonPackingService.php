<?php

namespace App\Services;

use App\Enums\RollStatus;
use App\Models\DtyCarton;
use App\Models\DtyRoll;
use App\Models\User;
use App\Support\BobbinWeightStandard;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartonPackingService
{
    public const BOBBINS_PER_CARTON = 6;

    public function initialize(DtyCarton $carton): void
    {
        if (! session()->has($this->sessionKey($carton))) {
            $this->reset($carton);
        }
    }

    public function reset(DtyCarton $carton): void
    {
        $ids = $carton->rolls()->orderBy('id')->pluck('id')->map(fn ($id): int => (int) $id)->all();

        session()->put($this->sessionKey($carton), $ids);
    }

    /**
     * @return Collection<int, DtyRoll>
     */
    public function scannedRolls(DtyCarton $carton): Collection
    {
        $ids = $this->sessionIds($carton);

        if ($ids === []) {
            return collect();
        }

        $rolls = DtyRoll::query()
            ->with('productionBatch')
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        return collect($ids)
            ->map(fn (int $id): ?DtyRoll => $rolls->get($id))
            ->filter()
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(DtyCarton $carton): array
    {
        $rolls = $this->scannedRolls($carton);
        $first = $rolls->first();

        return [
            'count' => $rolls->count(),
            'max' => self::BOBBINS_PER_CARTON,
            'remaining' => max(0, self::BOBBINS_PER_CARTON - $rolls->count()),
            'net_kg' => round($rolls->sum(fn (DtyRoll $roll): float => (float) $roll->berat_gram) / 1000, 2),
            'grade' => $carton->bobbin_weight_grade ?? $first?->grade,
            'merge' => $carton->nomor_merge,
            'production_date' => $carton->production_date->translatedFormat('d M Y'),
            'specification' => 'DTY '.$carton->specification,
            'rolls' => $rolls->map(fn (DtyRoll $roll): array => [
                'id' => $roll->id,
                'nomor_seri' => $roll->nomor_seri,
                'berat_gram' => (float) $roll->berat_gram,
                'grade' => $roll->grade,
                'nomor_lot' => $roll->nomor_lot,
                'current' => $roll->dty_carton_id === $carton->id,
            ])->values()->all(),
        ];
    }

    /**
     * @return array{success: bool, message: string, summary: array<string, mixed>}
     */
    public function scan(DtyCarton $carton, string $token): array
    {
        $token = trim($token);

        if ($token === '') {
            return $this->failure($carton, 'Masukkan nomor seri atau scan QR bobbin.');
        }

        $roll = DtyRoll::query()
            ->with('productionBatch')
            ->where(fn ($query) => $query
                ->where('nomor_seri', $token)
                ->orWhere('qr_token', $token))
            ->first();

        if ($roll === null) {
            return $this->failure($carton, 'Bobbin tidak ditemukan. Periksa QR atau nomor seri.');
        }

        $isCurrent = $roll->dty_carton_id === $carton->id;

        if ($isCurrent && $roll->status !== RollStatus::InStock) {
            return $this->failure($carton, 'Bobbin ini sudah tidak dapat diubah karena statusnya sudah berjalan.');
        }

        if (! $isCurrent && ($roll->status !== RollStatus::ReadyToPack || $roll->dty_carton_id !== null)) {
            return $this->failure($carton, "Bobbin {$roll->nomor_seri} tidak siap dipacking.");
        }

        if ($roll->nomor_lot !== $carton->nomor_merge) {
            return $this->failure($carton, "Bobbin {$roll->nomor_seri} berasal dari merge berbeda.");
        }

        $cartonGrade = $carton->bobbin_weight_grade;

        if ($cartonGrade !== null && $roll->grade !== $cartonGrade) {
            return $this->failure($carton, "Grade bobbin {$roll->nomor_seri} berbeda dari grade kardus ({$cartonGrade}).");
        }

        if ($roll->denier !== $carton->denier || $roll->filamen !== $carton->filamen) {
            return $this->failure($carton, 'Spesifikasi bobbin berbeda dari kardus.');
        }

        $ids = $this->sessionIds($carton);

        if (in_array($roll->id, $ids, true)) {
            return $this->failure($carton, 'Bobbin ini sudah ada dalam daftar kardus.');
        }

        if (count($ids) >= self::BOBBINS_PER_CARTON) {
            return $this->failure($carton, 'Kardus sudah berisi 6 bobbin.');
        }

        $ids[] = $roll->id;
        session()->put($this->sessionKey($carton), $ids);

        return [
            'success' => true,
            'message' => "Bobbin {$roll->nomor_seri} ditambahkan ke kardus.",
            'summary' => $this->summary($carton),
        ];
    }

    /**
     * @return array{success: bool, message: string, summary: array<string, mixed>}
     */
    public function remove(DtyCarton $carton, int $rollId): array
    {
        $ids = array_values(array_filter(
            $this->sessionIds($carton),
            fn (int $id): bool => $id !== $rollId,
        ));

        session()->put($this->sessionKey($carton), $ids);

        return [
            'success' => true,
            'message' => 'Bobbin dikeluarkan dari daftar kardus.',
            'summary' => $this->summary($carton),
        ];
    }

    /**
     * @param  array{gross_weight_kg: float|string, pallet_number?: ?string, catatan?: ?string}  $data
     */
    public function finalize(DtyCarton $carton, array $data, User $user): DtyCarton
    {
        $ids = $this->sessionIds($carton);

        if (count($ids) !== self::BOBBINS_PER_CARTON) {
            throw ValidationException::withMessages([
                'packing' => 'Isi kardus harus tepat 6 bobbin sebelum disimpan.',
            ]);
        }

        return DB::transaction(function () use ($carton, $ids, $data): DtyCarton {
            $lockedCarton = DtyCarton::query()->whereKey($carton->id)->lockForUpdate()->firstOrFail();
            $currentIds = $lockedCarton->rolls()->lockForUpdate()->pluck('id')->map(fn ($id): int => (int) $id)->all();
            $rolls = DtyRoll::query()
                ->with('productionBatch')
                ->whereIn('id', $ids)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($rolls->count() !== self::BOBBINS_PER_CARTON) {
                throw ValidationException::withMessages([
                    'packing' => 'Ada bobbin yang tidak ditemukan. Muat ulang halaman edit.',
                ]);
            }

            foreach ($ids as $id) {
                $roll = $rolls->get($id);
                $isCurrent = in_array($id, $currentIds, true);

                if ($roll === null
                    || $roll->nomor_lot !== $lockedCarton->nomor_merge
                    || $roll->denier !== $lockedCarton->denier
                    || $roll->filamen !== $lockedCarton->filamen
                    || (! $isCurrent && ($roll->status !== RollStatus::ReadyToPack || $roll->dty_carton_id !== null))
                    || ($isCurrent && ($roll->status !== RollStatus::InStock || $roll->dty_carton_id !== $lockedCarton->id))) {
                    throw ValidationException::withMessages([
                        'packing' => 'Daftar bobbin berubah atau tidak lagi valid. Muat ulang halaman edit.',
                    ]);
                }

                if ($lockedCarton->bobbin_weight_grade !== null && $roll->grade !== $lockedCarton->bobbin_weight_grade) {
                    throw ValidationException::withMessages([
                        'packing' => 'Grade bobbin tidak sama dengan grade kardus.',
                    ]);
                }
            }

            $netKg = round($rolls->sum(fn (DtyRoll $roll): float => (float) $roll->berat_gram) / 1000, 2);
            $grossKg = (float) $data['gross_weight_kg'];

            if ($grossKg <= $netKg) {
                throw ValidationException::withMessages([
                    'gross_weight_kg' => 'Gross weight harus lebih besar dari net weight.',
                ]);
            }

            $range = BobbinWeightStandard::grossRangeFor($lockedCarton->bobbin_weight_grade ?? '', self::BOBBINS_PER_CARTON);

            if ($range !== null && ($grossKg < $range['min'] || $grossKg > $range['max'])) {
                throw ValidationException::withMessages([
                    'gross_weight_kg' => 'Untuk Grade AX isi 6 bobbin, gross weight harus antara 34,74 dan 36,25 kg.',
                ]);
            }

            $palletNumber = $data['pallet_number'] ?? null;

            if ($palletNumber !== null
                && DtyCarton::query()
                    ->where('pallet_number', $palletNumber)
                    ->whereKeyNot($lockedCarton->id)
                    ->count() >= DtyCarton::PALLET_BOX_CAPACITY) {
                throw ValidationException::withMessages([
                    'pallet_number' => 'Pallet '.$palletNumber.' sudah berisi 30 kardus. Gunakan nomor pallet baru.',
                ]);
            }

            $removedIds = array_values(array_diff($currentIds, $ids));

            if ($removedIds !== []) {
                DtyRoll::query()
                    ->whereIn('id', $removedIds)
                    ->update([
                        'dty_carton_id' => null,
                        'status' => RollStatus::ReadyToPack->value,
                    ]);
            }

            DtyRoll::query()
                ->whereIn('id', $ids)
                ->update([
                    'dty_carton_id' => $lockedCarton->id,
                    'status' => RollStatus::InStock->value,
                ]);

            $lockedCarton->update([
                'net_weight_kg' => $netKg,
                'gross_weight_kg' => $grossKg,
                'unit_bobbins' => self::BOBBINS_PER_CARTON,
                'pallet_number' => $data['pallet_number'] ?? $lockedCarton->pallet_number,
                'catatan' => $data['catatan'] ?? null,
            ]);

            session()->forget($this->sessionKey($lockedCarton));

            return $lockedCarton;
        });
    }

    private function sessionKey(DtyCarton $carton): string
    {
        return "warehouse.carton_edit.{$carton->id}.roll_ids";
    }

    /**
     * @return list<int>
     */
    private function sessionIds(DtyCarton $carton): array
    {
        return collect(session()->get($this->sessionKey($carton), []))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array{success: false, message: string, summary: array<string, mixed>}
     */
    private function failure(DtyCarton $carton, string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'summary' => $this->summary($carton),
        ];
    }
}
