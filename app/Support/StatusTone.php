<?php

namespace App\Support;

use BackedEnum;

class StatusTone
{
    public static function for(BackedEnum|string $status): string
    {
        $value = $status instanceof BackedEnum ? $status->value : $status;

        return match ($value) {
            'admin' => 'brand',
            'admin_gudang' => 'warning',
            'logistik' => 'info',
            'driver' => 'success',

            'in_stock' => 'success',
            'allocated' => 'warning',
            'loaded' => 'info',
            'delivered' => 'success',
            'cancelled' => 'danger',

            'draft' => 'neutral',
            'issued' => 'warning',
            'loading' => 'info',
            'departed' => 'info',
            'in_transit' => 'info',
            'arrived' => 'success',
            'received' => 'success',

            'valid' => 'success',
            'wrong_do' => 'danger',
            'duplicate' => 'warning',
            'unknown' => 'neutral',
            'invalid_order' => 'danger',

            default => 'neutral',
        };
    }
}
