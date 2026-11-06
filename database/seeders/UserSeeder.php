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
                'nama' => 'Sari Puspita',
                'email' => 'admin@pmt.test',
                'role' => UserRole::Admin,
                'last_login_at' => now()->subMinutes(15),
            ],
            [
                'nama' => 'Budi Santoso',
                'email' => 'gudang@pmt.test',
                'role' => UserRole::AdminGudang,
                'last_login_at' => now()->subMinutes(35),
            ],
            [
                'nama' => 'Dewi Lestari',
                'email' => 'logistik@pmt.test',
                'role' => UserRole::Logistik,
                'last_login_at' => now()->subMinutes(20),
            ],
            [
                'nama' => 'Agus Setiawan',
                'email' => 'driver@pmt.test',
                'role' => UserRole::Driver,
                'last_login_at' => now()->subMinutes(5),
            ],
            [
                'nama' => 'Joko Santoso',
                'email' => 'driver2@pmt.test',
                'role' => UserRole::Driver,
                'last_login_at' => now()->subMinutes(60),
            ],
            [
                'nama' => 'Asep Sunandar',
                'email' => 'driver3@pmt.test',
                'role' => UserRole::Driver,
                'last_login_at' => now()->subMinutes(10),
            ],
            [
                'nama' => 'Rudi Hartono',
                'email' => 'driver4@pmt.test',
                'role' => UserRole::Driver,
                'last_login_at' => now()->subMinutes(45),
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
                    'last_login_at' => $account['last_login_at'],
                ],
            );
        }
    }
}
