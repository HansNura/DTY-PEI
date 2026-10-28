<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'nama' => 'Administrator',
                'email' => 'admin@pmt.test',
                'role' => UserRole::Admin,
            ],
            [
                'nama' => 'Admin Gudang',
                'email' => 'gudang@pmt.test',
                'role' => UserRole::AdminGudang,
            ],
            [
                'nama' => 'Tim Logistik',
                'email' => 'logistik@pmt.test',
                'role' => UserRole::Logistik,
            ],
            [
                'nama' => 'Driver',
                'email' => 'driver@pmt.test',
                'role' => UserRole::Driver,
            ],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                [
                    'nama' => $account['nama'],
                    'password' => 'password',
                    'role' => $account['role']->value,
                    'is_active' => true,
                ],
            );
        }
    }
}
