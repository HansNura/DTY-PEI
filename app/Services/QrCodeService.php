<?php

namespace App\Services;

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
}
