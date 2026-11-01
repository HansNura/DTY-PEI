<?php

namespace App\Enums;

enum LoadScanResult: string
{
    case Valid = 'valid';
    case WrongDo = 'wrong_do';
    case Duplicate = 'duplicate';
    case Unknown = 'unknown';
    case InvalidOrder = 'invalid_order';

    public function label(): string
    {
        return match ($this) {
            self::Valid => 'Valid / Dimuat',
            self::WrongDo => 'Salah Surat Jalan',
            self::Duplicate => 'Sudah Dipindai',
            self::Unknown => 'Kode QR Tidak Dikenal',
            self::InvalidOrder => 'Surat Jalan Tidak Aktif',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Valid => 'bg-emerald-100 text-emerald-800',
            self::WrongDo => 'bg-rose-100 text-rose-800',
            self::Duplicate => 'bg-amber-100 text-amber-800',
            self::Unknown => 'bg-slate-100 text-slate-700',
            self::InvalidOrder => 'bg-rose-100 text-rose-800',
        };
    }
}
