<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_batches', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_merge', 50)->unique();
            $table->unsignedInteger('denier');
            $table->unsignedInteger('filamen');
            $table->boolean('intermingled')->default(false);
            $table->string('twist_direction', 1)->nullable();
            $table->date('production_date')->index();
            $table->boolean('is_active')->default(false)->index();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_batches');
    }
};
