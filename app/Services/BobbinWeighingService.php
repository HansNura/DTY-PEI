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

    public function weigh(float $beratGram, User $user): DtyRoll
    {
        $batch = $this->activeBatch();

        if ($batch === null) {
            throw ValidationException::withMessages([
                'berat_gram' => 'Belum ada batch produksi aktif. Aktifkan merge terlebih dahulu.',
            ]);
        }

        if ($beratGram < BobbinWeightStandard::B_MIN_GRAM || $beratGram > BobbinWeightStandard::AX_MAX_GRAM) {
            throw ValidationException::withMessages([
                'berat_gram' => 'Berat bobbin harus berada di rentang 500–5.600 gram.',
            ]);
        }

        $grade = BobbinWeightStandard::gradeFor($beratGram);

        if ($grade === null) {
            throw ValidationException::withMessages([
                'berat_gram' => 'Berat bobbin berada di luar standar grade AX/AE/A/B.',
            ]);
        }

        return DtyRoll::create([
            'production_batch_id' => $batch->id,
            'kode_rol' => $this->qrCodeService->generateRollCode(),
            'nomor_seri' => $this->qrCodeService->generateRollSerial($batch->production_date),
            'grade' => $grade,
            'denier' => $batch->denier,
            'filamen' => $batch->filamen,
            'berat_gram' => $beratGram,
            'nomor_lot' => $batch->nomor_merge,
            'qr_token' => $this->qrCodeService->generateToken(),
            'status' => RollStatus::ReadyToPack,
            'created_by' => $user->id,
        ]);
    }
}
