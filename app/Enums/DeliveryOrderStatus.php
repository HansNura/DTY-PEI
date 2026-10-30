<?php

namespace App\Enums;

enum DeliveryOrderStatus: string
{
    case Draft = 'draft';
    case Issued = 'issued';
    case Loading = 'loading';
    case Departed = 'departed';
    case InTransit = 'in_transit';
    case Arrived = 'arrived';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Issued => 'Diterbitkan',
            self::Loading => 'Pemuatan',
            self::Departed => 'Berangkat',
            self::InTransit => 'Dalam Perjalanan',
            self::Arrived => 'Tiba di Tujuan',
            self::Received => 'Diterima',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'bg-slate-100 text-slate-700',
            self::Issued => 'bg-amber-100 text-amber-800',
            self::Loading => 'bg-blue-100 text-blue-800',
            self::Departed, self::InTransit => 'bg-indigo-100 text-indigo-800',
            self::Arrived => 'bg-purple-100 text-purple-800',
            self::Received => 'bg-emerald-100 text-emerald-800',
            self::Cancelled => 'bg-rose-100 text-rose-800',
        };
    }
}
