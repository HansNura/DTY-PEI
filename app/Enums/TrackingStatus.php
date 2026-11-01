<?php

namespace App\Enums;

enum TrackingStatus: string
{
    case Departed = 'departed';
    case InTransit = 'in_transit';
    case Arrived = 'arrived';

    public function label(): string
    {
        return match ($this) {
            self::Departed => 'Berangkat',
            self::InTransit => 'Dalam Perjalanan',
            self::Arrived => 'Tiba di Tujuan',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Departed => 'Armada meninggalkan gudang menuju pelanggan.',
            self::InTransit => 'Armada sedang dalam perjalanan. Kirim ulang untuk memperbarui posisi.',
            self::Arrived => 'Armada tiba di lokasi pelanggan.',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Departed => 'bg-indigo-100 text-indigo-800 ring-indigo-200',
            self::InTransit => 'bg-sky-100 text-sky-800 ring-sky-200',
            self::Arrived => 'bg-purple-100 text-purple-800 ring-purple-200',
        };
    }

    public function deliveryOrderStatus(): DeliveryOrderStatus
    {
        return match ($this) {
            self::Departed => DeliveryOrderStatus::Departed,
            self::InTransit => DeliveryOrderStatus::InTransit,
            self::Arrived => DeliveryOrderStatus::Arrived,
        };
    }
}
