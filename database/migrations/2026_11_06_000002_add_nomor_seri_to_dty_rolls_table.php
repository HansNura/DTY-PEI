<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dty_rolls', function (Blueprint $table) {
            $table->string('nomor_seri', 32)->nullable()->after('kode_rol');
        });

        $cartonDates = DB::table('dty_cartons')->pluck('production_date', 'id');
        $spindle = 20217;

        DB::table('dty_rolls')
            ->orderBy('id')
            ->get(['id', 'dty_carton_id', 'created_at'])
            ->each(function (object $roll) use (&$spindle, $cartonDates): void {
                $date = $roll->dty_carton_id ? $cartonDates[$roll->dty_carton_id] ?? null : null;
                $date = $date ?: $roll->created_at;
                $formattedDate = $date ? Carbon::parse($date)->format('Ymd') : now()->format('Ymd');

                DB::table('dty_rolls')
                    ->where('id', $roll->id)
                    ->update([
                        'nomor_seri' => str_pad((string) $spindle, 5, '0', STR_PAD_LEFT).'0079'.$formattedDate,
                    ]);

                $spindle++;
            });

        Schema::table('dty_rolls', function (Blueprint $table) {
            $table->unique('nomor_seri');
        });
    }

    public function down(): void
    {
        Schema::table('dty_rolls', function (Blueprint $table) {
            $table->dropUnique(['nomor_seri']);
            $table->dropColumn('nomor_seri');
        });
    }
};
