<?php

namespace App\Services;

use App\Enums\RollStatus;
use App\Models\DtyRoll;
use App\Models\ProductionBatch;
use App\Models\User;
use App\Support\BobbinWeightStandard;
use Illuminate\Validation\ValidationException;

class BobbinWeighingService
{
    public function __construct(
        private readonly QrCodeService $qrCodeService,
        private readonly ProductionBatchService $batchService,
    ) {}

    public function activeBatch(): ?ProductionBatch
    {
        return $this->batchService->active();
    }

    public function weigh(
        float $beratGram,
        User $user,
        string $nomorSeri,
    ): DtyRoll {
        $batch = $this->activeBatch();

        if ($batch === null) {
            throw ValidationException::withMessages([
                'berat_gram' => 'Belum ada batch produksi aktif. Aktifkan merge terlebih dahulu.',
            ]);
        }

        [$grade, $status] = $this->classification($beratGram);

        return DtyRoll::create([
            'production_batch_id' => $batch->id,
            'kode_rol' => $this->qrCodeService->generateRollCode(),
            'nomor_seri' => $nomorSeri,
            'grade' => $grade,
            'denier' => $batch->denier,
            'filamen' => $batch->filamen,
            'berat_gram' => $beratGram,
            'nomor_lot' => $batch->nomor_merge,
            'qr_token' => $this->qrCodeService->generateToken(),
            'status' => $status,
            'created_by' => $user->id,
        ]);
    }

    public function reweigh(DtyRoll $roll, float $beratGram, string $nomorSeri): DtyRoll
    {
        [$grade, $status] = $this->classification($beratGram);

        $roll->update([
            'berat_gram' => $beratGram,
            'nomor_seri' => $nomorSeri,
            'grade' => $grade,
            'status' => $status,
        ]);

        return $roll;
    }

    /**
     * @return array{0: string, 1: RollStatus}
     */
    private function classification(float $beratGram): array
    {
        $anomaly = BobbinWeightStandard::anomalyFor($beratGram);
        $grade = $anomaly ?? BobbinWeightStandard::gradeFor($beratGram);

        if ($grade === null) {
            throw ValidationException::withMessages([
                'berat_gram' => 'Berat bobbin tidak dapat diklasifikasikan.',
            ]);
        }

        $status = match ($anomaly) {
            BobbinWeightStandard::OVERWEIGHT_GRADE => RollStatus::Overweight,
            BobbinWeightStandard::WASTE_GRADE => RollStatus::Waste,
            default => RollStatus::ReadyToPack,
        };

        return [$grade, $status];
    }
}
