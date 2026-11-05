<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('kode_pelanggan', 30)->nullable()->unique()->after('id');
            $table->string('email', 150)->nullable()->after('nama_pabrik');
            $table->string('npwp', 30)->nullable()->after('email');
            $table->string('kontak_jabatan', 100)->nullable()->after('kontak_nama');
            $table->string('kota', 100)->nullable()->after('alamat');
            $table->string('provinsi', 100)->nullable()->after('kota');
            $table->string('kode_pos', 10)->nullable()->after('provinsi');
            $table->text('catatan')->nullable()->after('longitude');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['kode_pelanggan']);
            $table->dropColumn([
                'kode_pelanggan',
                'email',
                'npwp',
                'kontak_jabatan',
                'kota',
                'provinsi',
                'kode_pos',
                'catatan',
            ]);
        });
    }
};
