<?php

namespace App\Support;

use App\Enums\UserRole;
use App\Models\User;

class Navigation
{
    /**
     * @return array<int, array{label: string, items: array<int, array{label: string, route: string, icon: string, active: string}>}>
     */
    public static function groups(User $user): array
    {
        $dashboard = ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'dashboard', 'active' => 'dashboard'];
        $profile = ['label' => 'Profil', 'route' => 'profile.edit', 'icon' => 'user', 'active' => 'profile.*'];

        return match ($user->role) {
            UserRole::Admin => [
                ['label' => 'Operasional', 'items' => [
                    $dashboard,
                    ['label' => 'Pengguna', 'route' => 'admin.users.index', 'icon' => 'users', 'active' => 'admin.users.*'],
                ]],
                ['label' => 'Akun', 'items' => [$profile]],
            ],
            UserRole::AdminGudang => [
                ['label' => 'Operasional', 'items' => [
                    $dashboard,
                    ['label' => 'Packing Kardus', 'route' => 'warehouse.packing.create', 'icon' => 'boxes', 'active' => 'warehouse.packing.*'],
                    ['label' => 'Scanner Muatan', 'route' => 'warehouse.scans.index', 'icon' => 'scan', 'active' => 'warehouse.scans.*'],
                ]],
                ['label' => 'Master Data', 'items' => [
                    ['label' => 'Produksi', 'route' => 'warehouse.batches.index', 'icon' => 'activity', 'active' => 'warehouse.batches.*'],
                    ['label' => 'Kardus DTY', 'route' => 'warehouse.cartons.index', 'icon' => 'boxes', 'active' => 'warehouse.cartons.*'],
                    ['label' => 'Stok Bobbin', 'route' => 'warehouse.rolls.index', 'icon' => 'package', 'active' => 'warehouse.rolls.*'],
                ]],
                ['label' => 'Akun', 'items' => [$profile]],
            ],
            UserRole::Logistik => [
                ['label' => 'Operasional', 'items' => [
                    $dashboard,
                    ['label' => 'Surat Jalan', 'route' => 'logistics.orders.index', 'icon' => 'clipboard', 'active' => 'logistics.orders.*'],
                    ['label' => 'Live Tracking', 'route' => 'logistics.tracking.index', 'icon' => 'route', 'active' => 'logistics.tracking.*'],
                    ['label' => 'Rekap Pengiriman', 'route' => 'logistics.reports.deliveries', 'icon' => 'download', 'active' => 'logistics.reports.*'],
                ]],
                ['label' => 'Master Data', 'items' => [
                    ['label' => 'Pelanggan', 'route' => 'logistics.customers.index', 'icon' => 'building', 'active' => 'logistics.customers.*'],
                    ['label' => 'Armada', 'route' => 'logistics.fleets.index', 'icon' => 'truck', 'active' => 'logistics.fleets.*'],
                ]],
                ['label' => 'Akun', 'items' => [$profile]],
            ],
            UserRole::Driver => [
                ['label' => 'Perjalanan', 'items' => [
                    $dashboard,
                    ['label' => 'Perjalanan Saya', 'route' => 'driver.deliveries.index', 'icon' => 'truck', 'active' => 'driver.deliveries.*'],
                ]],
                ['label' => 'Akun', 'items' => [$profile]],
            ],
        };
    }

    /**
     * @return array<int, array{label: string, route: string, icon: string, active: string}>
     */
    public static function bottom(User $user): array
    {
        $dashboard = ['label' => 'Beranda', 'route' => 'dashboard', 'icon' => 'dashboard', 'active' => 'dashboard'];
        $profile = ['label' => 'Profil', 'route' => 'profile.edit', 'icon' => 'user', 'active' => 'profile.*'];

        return match ($user->role) {
            UserRole::Admin => [
                $dashboard,
                ['label' => 'Pengguna', 'route' => 'admin.users.index', 'icon' => 'users', 'active' => 'admin.users.*'],
                $profile,
            ],
            UserRole::AdminGudang => [
                $dashboard,
                ['label' => 'Kardus', 'route' => 'warehouse.cartons.index', 'icon' => 'boxes', 'active' => 'warehouse.cartons.*'],
                ['label' => 'Scan', 'route' => 'warehouse.scans.index', 'icon' => 'scan', 'active' => 'warehouse.scans.*'],
                $profile,
            ],
            UserRole::Logistik => [
                $dashboard,
                ['label' => 'Surat Jalan', 'route' => 'logistics.orders.index', 'icon' => 'clipboard', 'active' => 'logistics.orders.*'],
                ['label' => 'Tracking', 'route' => 'logistics.tracking.index', 'icon' => 'route', 'active' => 'logistics.tracking.*'],
                $profile,
            ],
            UserRole::Driver => [
                $dashboard,
                ['label' => 'Perjalanan', 'route' => 'driver.deliveries.index', 'icon' => 'truck', 'active' => 'driver.deliveries.*'],
                $profile,
            ],
        };
    }
}
