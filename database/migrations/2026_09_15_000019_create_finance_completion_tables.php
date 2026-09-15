<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_discounts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_invoice_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('scholarship_award_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind')->default('other');
            $table->decimal('amount', 15, 2);
            $table->text('reason')->nullable();
            $table->foreignUlid('granted_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('granted_at');
            $table->timestamps();
            $table->index(['student_invoice_id', 'kind']);
        });

        Schema::create('invoice_penalties', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_invoice_id')->constrained()->cascadeOnDelete();
            $table->string('kind')->default('late');
            $table->decimal('amount', 15, 2);
            $table->text('reason')->nullable();
            $table->string('status')->default('applied');
            $table->foreignUlid('applied_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('applied_at');
            $table->foreignUlid('waived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('waived_at')->nullable();
            $table->timestamps();
            $table->index(['student_invoice_id', 'status']);
        });

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('integration_endpoint_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('student_invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider');
            $table->string('external_id')->nullable()->unique();
            $table->string('idempotency_key')->unique();
            $table->decimal('amount', 18, 2);
            $table->string('status')->default('pending');
            $table->json('request_payload')->nullable();
            $table->json('callback_payload')->nullable();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->text('last_error')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->index(['university_id', 'provider', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('invoice_penalties');
        Schema::dropIfExists('invoice_discounts');
    }
};
