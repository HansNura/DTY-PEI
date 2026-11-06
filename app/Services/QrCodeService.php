<?php

namespace App\Services;

use App\Models\DtyCarton;
use Illuminate\Support\Str;

class QrCodeService
{
    public function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    public function generateRollCode(): string
    {
        return 'DTY-'.strtoupper((string) Str::ulid());
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
