<?php

namespace Database\Seeders;

use App\Models\Fleet;
use App\Models\User;
use Illuminate\Database\Seeder;

class FleetSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(UserSeeder::class);

        $drivers = User::query()
            ->whereIn('email', [
                'driver@pmt.test',
                'driver2@pmt.test',
                'driver3@pmt.test',
                'driver4@pmt.test',
            ])
            ->pluck('id', 'email');

        $fleets = [
            [
                'kode_armada' => 'ARM-0001',
                'nomor_polisi' => 'B 1234 PMT',
                'jenis_kendaraan' => 'Truk Box',
                'kapasitas_kg' => 5000,
                'driver_id' => $drivers['driver@pmt.test'] ?? null,
                'is_active' => true,
            ],
            [
                'kode_armada' => 'ARM-0002',
                'nomor_polisi' => 'B 9876 XYZ',
                'jenis_kendaraan' => 'Truk Fuso',
                'kapasitas_kg' => 8000,
                'driver_id' => $drivers['driver2@pmt.test'] ?? null,
                'is_active' => true,
            ],
            [
                'kode_armada' => 'ARM-0003',
                'nomor_polisi' => 'D 2345 AB',
                'jenis_kendaraan' => 'Truk Wingbox',
                'kapasitas_kg' => 6000,
                'driver_id' => $drivers['driver3@pmt.test'] ?? null,
                'is_active' => true,
            ],
            [
                'kode_armada' => 'ARM-0004',
                'nomor_polisi' => 'F 7788 CD',
                'jenis_kendaraan' => 'Truk Engkel',
                'kapasitas_kg' => 3000,
                'driver_id' => $drivers['driver4@pmt.test'] ?? null,
                'is_active' => true,
            ],
            [
                'kode_armada' => 'ARM-0005',
                'nomor_polisi' => 'L 9012 EF',
                'jenis_kendaraan' => 'Truk Tronton',
                'kapasitas_kg' => 15000,
                'driver_id' => null,
                'is_active' => true,
            ],
            [
                'kode_armada' => 'ARM-0006',
                'nomor_polisi' => 'H 3456 GH',
                'jenis_kendaraan' => 'Truk Box',
                'kapasitas_kg' => 4000,
                'driver_id' => null,
                'is_active' => false,
            ],
        ];

        foreach ($fleets as $fleet) {
            Fleet::updateOrCreate(
                ['nomor_polisi' => $fleet['nomor_polisi']],
                $fleet,
            );
        }
    }
}
