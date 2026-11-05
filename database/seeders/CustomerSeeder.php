<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'kode_pelanggan' => 'CUST-0001',
                'nama_pabrik' => 'PT Demo Tekstil',
                'email' => 'purchasing@demotekstil.co.id',
                'npwp' => '01.234.567.8-901.000',
                'alamat' => 'Jl. Industri No. 10',
                'kota' => 'Bandung',
                'provinsi' => 'Jawa Barat',
                'kode_pos' => '40123',
                'kontak_nama' => 'Rina',
                'kontak_jabatan' => 'Purchasing',
                'kontak_telepon' => '081200000001',
                'latitude' => -6.9175000,
                'longitude' => 107.6191000,
                'catatan' => 'Data demo. Pembeli DTY untuk produksi kain rajut.',
                'is_active' => true,
            ],
            [
                'kode_pelanggan' => 'CUST-0002',
                'nama_pabrik' => 'PT Sinar Benang Nusantara',
                'email' => 'purchasing@sinarbenang.co.id',
                'npwp' => '02.345.678.9-012.000',
                'alamat' => 'Jl. Soekarno-Hatta No. 88',
                'kota' => 'Bandung',
                'provinsi' => 'Jawa Barat',
                'kode_pos' => '40286',
                'kontak_nama' => 'Dedi Kurniawan',
                'kontak_jabatan' => 'Manager Purchasing',
                'kontak_telepon' => '081200000002',
                'latitude' => -6.9430000,
                'longitude' => 107.6350000,
                'catatan' => 'Pembeli DTY untuk benang rajut dan kain jadi.',
                'is_active' => true,
            ],
            [
                'kode_pelanggan' => 'CUST-0003',
                'nama_pabrik' => 'PT Karya Rajut Sentosa',
                'email' => 'maya@karyarajut.co.id',
                'npwp' => '03.456.789.0-123.000',
                'alamat' => 'Jl. Raya Serpong No. 21',
                'kota' => 'Tangerang Selatan',
                'provinsi' => 'Banten',
                'kode_pos' => '15326',
                'kontak_nama' => 'Maya Lestari',
                'kontak_jabatan' => 'Purchasing',
                'kontak_telepon' => '081200000003',
                'latitude' => -6.3210000,
                'longitude' => 106.6650000,
                'catatan' => 'Pembeli DTY untuk produksi rajutan.',
                'is_active' => true,
            ],
            [
                'kode_pelanggan' => 'CUST-0004',
                'nama_pabrik' => 'PT Mitra Tenun Indonesia',
                'email' => 'agus@mitratenun.co.id',
                'npwp' => '04.567.890.1-234.000',
                'alamat' => 'Jl. Slamet Riyadi No. 45',
                'kota' => 'Surakarta',
                'provinsi' => 'Jawa Tengah',
                'kode_pos' => '57141',
                'kontak_nama' => 'Agus Santoso',
                'kontak_jabatan' => 'Procurement',
                'kontak_telepon' => '081200000004',
                'latitude' => -7.5667000,
                'longitude' => 110.8167000,
                'catatan' => 'Pembeli DTY untuk kain tenun.',
                'is_active' => true,
            ],
            [
                'kode_pelanggan' => 'CUST-0005',
                'nama_pabrik' => 'PT Anugerah Tekstil Jaya',
                'email' => 'siti@anugerahtekstil.co.id',
                'npwp' => '05.678.901.2-345.000',
                'alamat' => 'Jl. Rungkut Industri No. 12',
                'kota' => 'Surabaya',
                'provinsi' => 'Jawa Timur',
                'kode_pos' => '60293',
                'kontak_nama' => 'Siti Aminah',
                'kontak_jabatan' => 'Purchasing',
                'kontak_telepon' => '081200000005',
                'latitude' => -7.3161000,
                'longitude' => 112.7750000,
                'catatan' => 'Pembeli DTY untuk kain rajut dan tenun.',
                'is_active' => true,
            ],
            [
                'kode_pelanggan' => 'CUST-0006',
                'nama_pabrik' => 'PT Global Knitting Manufacture',
                'email' => 'hendra@globalknitting.co.id',
                'npwp' => '06.789.012.3-456.000',
                'alamat' => 'Jl. Industri Selatan Blok C No. 7',
                'kota' => 'Bekasi',
                'provinsi' => 'Jawa Barat',
                'kode_pos' => '17530',
                'kontak_nama' => 'Hendra Wijaya',
                'kontak_jabatan' => 'Manager Procurement',
                'kontak_telepon' => '081200000006',
                'latitude' => -6.2383000,
                'longitude' => 106.9756000,
                'catatan' => 'Pembeli DTY untuk produksi rajutan skala ekspor.',
                'is_active' => true,
            ],
        ];

        foreach ($customers as $customer) {
            Customer::updateOrCreate(
                ['nama_pabrik' => $customer['nama_pabrik']],
                $customer,
            );
        }
    }
}
