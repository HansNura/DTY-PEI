<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('load_scans', function (Blueprint $table) {
            $table->foreignId('dty_carton_id')
                ->nullable()
                ->after('dty_roll_id')
                ->constrained('dty_cartons')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('load_scans', function (Blueprint $table) {
            $table->dropForeign(['dty_carton_id']);
            $table->dropColumn('dty_carton_id');
        });
    }
};
