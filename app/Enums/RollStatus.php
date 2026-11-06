<?php

namespace App\Enums;

enum RollStatus: string
{
    case ReadyToPack = 'ready_to_pack';
    case InStock = 'in_stock';
    case Allocated = 'allocated';
    case Loaded = 'loaded';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::ReadyToPack => 'Siap packing',
            self::InStock => 'Tersedia',
            self::Allocated => 'Dialokasikan',
            self::Loaded => 'Dimuat',
            self::Delivered => 'Terkirim',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
