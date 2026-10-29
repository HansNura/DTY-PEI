<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dty_rolls', function (Blueprint $table) {
            $table->id();
            $table->string('kode_rol', 50)->unique();
            $table->unsignedInteger('denier');
            $table->unsignedInteger('filamen');
            $table->decimal('berat_gram', 10, 2);
            $table->string('nomor_lot', 50)->index();
            $table->string('qr_token', 64)->unique();
            $table->string('status', 20)->default('in_stock')->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dty_rolls');
    }
};
