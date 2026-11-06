<?php

namespace App\Support;

use App\Enums\UserRole;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;

class AccessMatrix
{
    /**
     * @return array<int, array{
     *     key: string,
     *     group: string,
     *     label: string,
     *     description: string,
     *     route: ?string,
     *     icon: string,
     *     patterns: array<int, string>,
     *     roles: array<int, UserRole>
     * }>
     */
    public static function modules(): array
    {
        $definitions = [
            [
                'key' => 'dashboard',
                'group' => 'Umum',
                'label' => 'Dashboard',
                'description' => 'Ringkasan operasional sesuai peran pengguna.',
                'route' => 'dashboard',
                'icon' => 'dashboard',
                'patterns' => ['dashboard'],
            ],
            [
                'key' => 'profile',
                'group' => 'Umum',
                'label' => 'Profil & kata sandi',
                'description' => 'Ubah identitas dan kata sandi akun sendiri.',
                'route' => 'profile.edit',
                'icon' => 'user',
                'patterns' => ['profile.*'],
            ],
            [
                'key' => 'admin.users',
                'group' => 'Administrasi',
                'label' => 'Manajemen pengguna',
                'description' => 'Tambah, ubah, dan aktifkan atau nonaktifkan akun.',
                'route' => 'admin.users.index',
                'icon' => 'users',
                'patterns' => ['admin.users.*'],
            ],
            [
                'key' => 'admin.access',
                'group' => 'Administrasi',
                'label' => 'Hak akses & peran',
                'description' => 'Laporan matriks hak akses seluruh peran.',
                'route' => 'admin.access.index',
                'icon' => 'shield',
                'patterns' => ['admin.access.*'],
            ],
            [
                'key' => 'warehouse.batches',
                'group' => 'Gudang',
                'label' => 'Produksi & merge',
                'description' => 'Kelola batch produksi, merge, dan spesifikasi.',
                'route' => 'warehouse.batches.index',
                'icon' => 'activity',
                'patterns' => ['warehouse.batches.*'],
            ],
            [
                'key' => 'warehouse.cartons',
                'group' => 'Gudang',
                'label' => 'Kardus DTY',
                'description' => 'Kelola isi, berat, dan label kardus.',
                'route' => 'warehouse.cartons.index',
                'icon' => 'boxes',
                'patterns' => ['warehouse.cartons.*'],
            ],
            [
                'key' => 'warehouse.rolls',
                'group' => 'Gudang',
                'label' => 'Stok bobbin',
                'description' => 'Catat, timbang, ubah, dan batalkan bobbin.',
                'route' => 'warehouse.rolls.index',
                'icon' => 'package',
                'patterns' => ['warehouse.rolls.*'],
            ],
            [
                'key' => 'warehouse.packing',
                'group' => 'Gudang',
                'label' => 'Packing kardus',
                'description' => 'Pindai dan susun bobbin ke dalam kardus.',
                'route' => 'warehouse.packing.create',
                'icon' => 'boxes',
                'patterns' => ['warehouse.packing.*'],
            ],
            [
                'key' => 'warehouse.scans',
                'group' => 'Gudang',
                'label' => 'Scanner muatan',
                'description' => 'Validasi kardus yang dimuat ke armada.',
                'route' => 'warehouse.scans.index',
                'icon' => 'scan',
                'patterns' => ['warehouse.scans.*'],
            ],
            [
                'key' => 'logistics.customers',
                'group' => 'Logistik',
                'label' => 'Pelanggan',
                'description' => 'Kelola data dan titik lokasi pelanggan.',
                'route' => 'logistics.customers.index',
                'icon' => 'building',
                'patterns' => ['logistics.customers.*'],
            ],
            [
                'key' => 'logistics.fleets',
                'group' => 'Logistik',
                'label' => 'Armada',
                'description' => 'Kelola kendaraan, kapasitas, dan driver utama.',
                'route' => 'logistics.fleets.index',
                'icon' => 'truck',
                'patterns' => ['logistics.fleets.*'],
            ],
            [
                'key' => 'logistics.orders',
                'group' => 'Logistik',
                'label' => 'Surat jalan',
                'description' => 'Terbitkan, cetak, unduh, dan batalkan surat jalan.',
                'route' => 'logistics.orders.index',
                'icon' => 'clipboard',
                'patterns' => ['logistics.orders.*'],
            ],
            [
                'key' => 'logistics.tracking',
                'group' => 'Logistik',
                'label' => 'Live tracking',
                'description' => 'Pantau posisi dan riwayat perjalanan armada.',
                'route' => 'logistics.tracking.index',
                'icon' => 'route',
                'patterns' => ['logistics.tracking.*'],
            ],
            [
                'key' => 'logistics.reports',
                'group' => 'Logistik',
                'label' => 'Rekap pengiriman',
                'description' => 'Unduh laporan pengiriman dalam PDF dan Excel.',
                'route' => 'logistics.reports.deliveries',
                'icon' => 'download',
                'patterns' => ['logistics.reports.*'],
            ],
            [
                'key' => 'driver.deliveries',
                'group' => 'Driver',
                'label' => 'Perjalanan driver',
                'description' => 'Lihat surat jalan sendiri dan perbarui status perjalanan.',
                'route' => 'driver.deliveries.index',
                'icon' => 'truck',
                'patterns' => ['driver.deliveries.*'],
            ],
            [
                'key' => 'deliveries.signature',
                'group' => 'Driver',
                'label' => 'Tanda tangan & POD',
                'description' => 'Lihat bukti tanda tangan penerimaan pelanggan.',
                'route' => null,
                'icon' => 'edit',
                'patterns' => ['deliveries.signature'],
            ],
        ];

        $modules = [];

        foreach ($definitions as $definition) {
            $patterns = $definition['patterns'];
            unset($definition['patterns']);
            $definition['roles'] = self::rolesForRoutes($patterns);
            $modules[] = $definition;
        }

        return $modules;
    }

    /**
     * @param  array<int, string>  $patterns
     * @return array<int, UserRole>
     */
    private static function rolesForRoutes(array $patterns): array
    {
        $roles = collect(RouteFacade::getRoutes()->getRoutes())
            ->filter(fn (Route $route): bool => $route->getName() !== null
                && Str::is($patterns, $route->getName()))
            ->flatMap(fn (Route $route): array => self::rolesForRoute($route))
            ->unique(fn (UserRole $role): string => $role->value)
            ->all();

        return array_values(array_filter(
            UserRole::cases(),
            fn (UserRole $role): bool => in_array($role, $roles, true),
        ));
    }

    /**
     * @return array<int, UserRole>
     */
    private static function rolesForRoute(Route $route): array
    {
        $roleValues = [];

        foreach ($route->gatherMiddleware() as $middleware) {
            if (! is_string($middleware) || ! str_starts_with($middleware, 'role:')) {
                continue;
            }

            foreach (explode(',', substr($middleware, 5)) as $role) {
                $roleValues[] = trim($role);
            }
        }

        if ($roleValues === []) {
            return UserRole::cases();
        }

        return array_values(array_filter(array_map(
            fn (string $role): ?UserRole => UserRole::tryFrom($role),
            $roleValues,
        )));
    }
}
