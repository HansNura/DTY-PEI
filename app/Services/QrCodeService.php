<?php

namespace App\Services;

use App\Models\DtyRoll;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class QrCodeService
{
    private const BOBBIN_MACHINE_CODE = '0079';

    public function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function generateRollCode(): string
    {
        return 'DTY-'.strtoupper((string) Str::ulid());
    }

    public function generateRollSerial(?CarbonInterface $date = null): string
    {
        $lastSerial = DtyRoll::query()->whereNotNull('nomor_seri')->max('nomor_seri');
        $spindle = $lastSerial !== null ? ((int) substr((string) $lastSerial, 0, 5)) + 1 : 20217;
        $productionDate = $date ?? now();

        return str_pad((string) $spindle, 5, '0', STR_PAD_LEFT)
            .self::BOBBIN_MACHINE_CODE
            .$productionDate->format('Ymd');
    }
}
