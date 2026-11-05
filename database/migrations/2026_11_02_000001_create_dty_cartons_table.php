<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dty_cartons', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_hu', 50)->unique();
            $table->string('nomor_merge', 50)->index();
            $table->string('grade', 10);
            $table->date('production_date')->index();
            $table->unsignedInteger('denier');
            $table->unsignedInteger('filamen');
            $table->boolean('intermingled')->default(false);
            $table->string('twist_direction', 1)->nullable();
            $table->decimal('gross_weight_kg', 10, 2);
            $table->decimal('net_weight_kg', 10, 2);
            $table->unsignedSmallInteger('unit_bobbins');
            $table->string('pallet_number', 50)->nullable()->index();
            $table->string('qr_token', 64)->unique();
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dty_cartons');
    }
};
