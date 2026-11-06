<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fleets', function (Blueprint $table) {
            $table->string('kode_armada', 30)->nullable()->after('id');
        });

        DB::table('fleets')
            ->orderBy('id')
            ->get(['id'])
            ->each(function (object $fleet): void {
                DB::table('fleets')
                    ->where('id', $fleet->id)
                    ->update([
                        'kode_armada' => 'ARM-'.str_pad((string) $fleet->id, 4, '0', STR_PAD_LEFT),
                    ]);
            });

        Schema::table('fleets', function (Blueprint $table) {
            $table->unique('kode_armada');
        });
    }

    public function down(): void
    {
        Schema::table('fleets', function (Blueprint $table) {
            $table->dropUnique(['kode_armada']);
            $table->dropColumn('kode_armada');
        });
    }
};
