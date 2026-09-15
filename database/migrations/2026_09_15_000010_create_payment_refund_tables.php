<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_refunds', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('payment_id')->constrained()->restrictOnDelete();
            $table->string('refund_number')->unique();
            $table->decimal('amount', 18, 2);
            $table->text('reason');
            $table->string('status')->default('completed');
            $table->foreignUlid('processed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('completed_at');
            $table->timestamps();
            $table->index(['payment_id', 'status', 'completed_at']);
        });

        Schema::create('payment_refund_allocations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('payment_refund_id')->constrained('payment_refunds')->restrictOnDelete();
            $table->foreignUlid('payment_allocation_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 18, 2);
            $table->timestamps();
            $table->unique(['payment_refund_id', 'payment_allocation_id'], 'refund_allocation_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_refund_allocations');
        Schema::dropIfExists('payment_refunds');
    }
};
