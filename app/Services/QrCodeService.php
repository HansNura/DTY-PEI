<?php

namespace App\Services;

use App\Models\DtyCarton;
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

    public function generateCartonHu(): string
    {
        $lastHu = DtyCarton::query()
            ->where('nomor_hu', 'like', 'ADMG%')
            ->orderByDesc('nomor_hu')
            ->value('nomor_hu');

        $next = 1;

        if ($lastHu !== null && preg_match('/(\d+)$/', (string) $lastHu, $matches)) {
            $next = ((int) $matches[1]) + 1;
        }

        do {
            $hu = 'ADMG'.str_pad((string) $next, 8, '0', STR_PAD_LEFT);
            $next++;
        } while (DtyCarton::query()->where('nomor_hu', $hu)->exists());

        return $hu;
    }
}
