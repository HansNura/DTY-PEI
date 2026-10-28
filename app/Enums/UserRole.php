<?php

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case AdminGudang = 'admin_gudang';
    case Logistik = 'logistik';
    case Driver = 'driver';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrator',
            self::AdminGudang => 'Admin Gudang',
            self::Logistik => 'Tim Logistik',
            self::Driver => 'Driver',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Admin => 'Mengelola akun pengguna dan hak akses sistem.',
            self::AdminGudang => 'Mengelola master data DTY, QR code, dan validasi muatan.',
            self::Logistik => 'Mengelola pelanggan, armada, surat jalan, dan pemantauan pengiriman.',
            self::Driver => 'Memperbarui status perjalanan dan konfirmasi bukti terima.',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this) {
            self::Admin => 'bg-violet-50 text-violet-700 ring-violet-200',
            self::AdminGudang => 'bg-amber-50 text-amber-700 ring-amber-200',
            self::Logistik => 'bg-sky-50 text-sky-700 ring-sky-200',
            self::Driver => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
