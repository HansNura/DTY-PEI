<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dty_rolls', function (Blueprint $table) {
            $table->foreignId('production_batch_id')
                ->nullable()
                ->after('dty_carton_id')
                ->constrained('production_batches')
                ->nullOnDelete();
            $table->string('grade', 10)->nullable()->after('nomor_seri')->index();
        });

        DB::table('dty_rolls')
            ->orderBy('id')
            ->get(['id', 'berat_gram'])
            ->each(function (object $roll): void {
                $grams = (float) $roll->berat_gram;

                $grade = match (true) {
                    $grams >= 5350 && $grams <= 5600 => 'AX',
                    $grams >= 5100 && $grams < 5350 => 'AE',
                    $grams >= 1000 && $grams < 5100 => 'A',
                    $grams >= 500 && $grams < 1000 => 'B',
                    default => null,
                };

                DB::table('dty_rolls')->where('id', $roll->id)->update(['grade' => $grade]);
            });
    }

    public function down(): void
    {
        Schema::table('dty_rolls', function (Blueprint $table) {
            $table->dropForeign(['production_batch_id']);
            $table->dropColumn(['production_batch_id', 'grade']);
        });
    }
};
