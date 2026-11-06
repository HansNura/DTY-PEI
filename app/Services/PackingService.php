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

class PackingService
{
    public const BOBBINS_PER_CARTON = 6;

    private const SESSION_KEY = 'warehouse.packing.roll_ids';

    public function __construct(
        private readonly QrCodeService $qrCodeService,
    ) {}

    /**
     * @return Collection<int, DtyRoll>
     */
    public function scannedRolls(): Collection
    {
        $ids = collect(session()->get(self::SESSION_KEY, []))
            ->map(fn ($id): int => (int) $id)
            ->all();

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
    public function summary(): array
    {
        $rolls = $this->scannedRolls();
        $first = $rolls->first();

        return [
            'count' => $rolls->count(),
            'max' => self::BOBBINS_PER_CARTON,
            'remaining' => max(0, self::BOBBINS_PER_CARTON - $rolls->count()),
            'net_kg' => round($rolls->sum(fn (DtyRoll $roll): float => (float) $roll->berat_gram) / 1000, 2),
            'grade' => $first?->grade,
            'merge' => $first?->nomor_lot,
            'specification' => $first?->specification,
            'rolls' => $rolls->map(fn (DtyRoll $roll): array => [
                'id' => $roll->id,
                'nomor_seri' => $roll->nomor_seri,
                'berat_gram' => (float) $roll->berat_gram,
                'grade' => $roll->grade,
                'nomor_lot' => $roll->nomor_lot,
            ])->values()->all(),
        ];
    }

    /**
     * @return array{success: bool, message: string, summary: array<string, mixed>}
     */
    public function scan(string $token): array
    {
        $token = trim($token);

        if ($token === '') {
            return $this->failure('Masukkan nomor seri atau scan QR bobbin.');
        }

        $roll = DtyRoll::query()
            ->with('productionBatch')
            ->where(fn ($query) => $query
                ->where('nomor_seri', $token)
                ->orWhere('qr_token', $token))
            ->first();

        if ($roll === null) {
            return $this->failure('Bobbin tidak ditemukan. Periksa QR atau nomor seri.');
        }

        if ($roll->status === RollStatus::Overweight) {
            return $this->failure("Bobbin {$roll->nomor_seri} berstatus OVERWEIGHT / HOLD dan tidak diizinkan masuk ke Box grade normal.");
        }

        if ($roll->status === RollStatus::Waste) {
            return $this->failure("Bobbin {$roll->nomor_seri} berstatus WASTE / SCRAP dan tidak diizinkan masuk ke Box grade normal.");
        }

        if (in_array($roll->grade, [BobbinWeightStandard::OVERWEIGHT_GRADE, BobbinWeightStandard::WASTE_GRADE], true)) {
            return $this->failure("Bobbin {$roll->nomor_seri} memiliki grade anomali {$roll->grade} dan tidak diizinkan masuk ke Box grade normal.");
        }

        if ($roll->status !== RollStatus::ReadyToPack) {
            return $this->failure("Bobbin {$roll->nomor_seri} berstatus {$roll->status->label()} dan tidak siap dipacking.");
        }

        if ($roll->dty_carton_id !== null) {
            return $this->failure('Bobbin ini sudah masuk ke kardus lain.');
        }

        if ($roll->production_batch_id === null) {
            return $this->failure('Bobbin tidak memiliki batch produksi. Timbang ulang dari batch aktif.');
        }

        $ids = $this->sessionIds();

        if (in_array($roll->id, $ids, true)) {
            return $this->failure('Bobbin ini sudah ada dalam daftar packing.');
        }

        if (count($ids) >= self::BOBBINS_PER_CARTON) {
            return $this->failure('Kardus sudah berisi 6 bobbin.');
        }

        if ($ids !== []) {
            $first = DtyRoll::query()->find($ids[0]);

            if ($first === null) {
                $this->reset();

                return $this->failure('Daftar packing tidak valid. Silakan mulai ulang.');
            }

            if ($first->production_batch_id !== $roll->production_batch_id) {
                return $this->failure('Merge/batch bobbin tidak sama dengan bobbin pertama.');
            }

            if ($first->grade !== $roll->grade) {
                return $this->failure("Grade bobbin tidak sama ({$first->grade} vs {$roll->grade}). Pisahkan grade yang berbeda.");
            }
        }

        if ($roll->grade === null) {
            return $this->failure('Grade bobbin belum tersedia. Timbang ulang bobbin.');
        }

        $ids[] = $roll->id;
        session()->put(self::SESSION_KEY, $ids);

        return [
            'success' => true,
            'message' => "Bobbin {$roll->nomor_seri} ditambahkan ke packing.",
            'summary' => $this->summary(),
        ];
    }

    /**
     * @return array{success: bool, message: string, summary: array<string, mixed>}
     */
    public function remove(int $rollId): array
    {
        $ids = array_values(array_filter(
            $this->sessionIds(),
            fn (int $id): bool => $id !== $rollId,
        ));

        session()->put(self::SESSION_KEY, $ids);

        return [
            'success' => true,
            'message' => 'Bobbin dikeluarkan dari daftar packing.',
            'summary' => $this->summary(),
        ];
    }

    public function reset(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * @param  array{gross_weight_kg: float|string, pallet_number?: ?string, catatan?: ?string}  $data
     */
    public function finalize(array $data, User $user): DtyCarton
    {
        $ids = $this->sessionIds();

        if (count($ids) !== self::BOBBINS_PER_CARTON) {
            throw ValidationException::withMessages([
                'packing' => 'Packing harus berisi tepat 6 bobbin sebelum diselesaikan.',
            ]);
        }

        return DB::transaction(function () use ($ids, $data, $user): DtyCarton {
            $rolls = DtyRoll::query()
                ->with('productionBatch')
                ->whereIn('id', $ids)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($rolls->count() !== self::BOBBINS_PER_CARTON) {
                throw ValidationException::withMessages([
                    'packing' => 'Ada bobbin yang tidak ditemukan. Mulai ulang packing.',
                ]);
            }

            $first = $rolls->get($ids[0]);
            $batch = $first?->productionBatch;
            $grade = $first?->grade;

            if ($first === null || $batch === null || $grade === null) {
                throw ValidationException::withMessages([
                    'packing' => 'Data batch/grade bobbin tidak lengkap.',
                ]);
            }

            foreach ($ids as $id) {
                $roll = $rolls->get($id);

                if ($roll === null
                    || $roll->status !== RollStatus::ReadyToPack
                    || $roll->dty_carton_id !== null
                    || $roll->production_batch_id !== $batch->id
                    || $roll->grade !== $grade
                    || in_array($roll->grade, [BobbinWeightStandard::OVERWEIGHT_GRADE, BobbinWeightStandard::WASTE_GRADE], true)) {
                    throw ValidationException::withMessages([
                        'packing' => 'Daftar bobbin berubah atau tidak lagi valid. Mulai ulang packing.',
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

            $range = BobbinWeightStandard::grossRangeFor($grade, self::BOBBINS_PER_CARTON);

            if ($range !== null && ($grossKg < $range['min'] || $grossKg > $range['max'])) {
                throw ValidationException::withMessages([
                    'gross_weight_kg' => 'Untuk Grade AX isi 6 bobbin, gross weight harus antara 34,74 dan 36,25 kg.',
                ]);
            }

            $carton = DtyCarton::create([
                'nomor_hu' => $this->qrCodeService->generateCartonHu(),
                'nomor_merge' => $batch->nomor_merge,
                'grade' => $grade,
                'production_date' => now()->toDateString(),
                'denier' => $batch->denier,
                'filamen' => $batch->filamen,
                'intermingled' => $batch->intermingled,
                'twist_direction' => $batch->twist_direction,
                'gross_weight_kg' => $grossKg,
                'net_weight_kg' => $netKg,
                'unit_bobbins' => self::BOBBINS_PER_CARTON,
                'pallet_number' => $data['pallet_number'] ?? null,
                'qr_token' => $this->qrCodeService->generateToken(),
                'catatan' => $data['catatan'] ?? null,
                'created_by' => $user->id,
            ]);

            DtyRoll::query()
                ->whereIn('id', $ids)
                ->update([
                    'dty_carton_id' => $carton->id,
                    'status' => RollStatus::InStock->value,
                ]);

            $this->reset();

            return $carton;
        });
    }

    /**
     * @return list<int>
     */
    private function sessionIds(): array
    {
        return collect(session()->get(self::SESSION_KEY, []))
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array{success: false, message: string, summary: array<string, mixed>}
     */
    private function failure(string $message): array
    {
        return [
            'success' => false,
            'message' => $message,
            'summary' => $this->summary(),
        ];
    }
}
