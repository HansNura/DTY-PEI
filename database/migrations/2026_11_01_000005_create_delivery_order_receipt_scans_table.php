<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_order_receipt_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_order_id')->constrained()->restrictOnDelete();
            $table->foreignId('dty_roll_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('scanned_by')->constrained('users')->restrictOnDelete();
            $table->string('result', 20);
            $table->timestamp('scanned_at');
            $table->timestamps();
            $table->index(['delivery_order_id', 'scanned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_order_receipt_scans');
    }
};
