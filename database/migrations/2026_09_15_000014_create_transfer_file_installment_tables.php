<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_transfers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('from_study_program_id')->constrained('study_programs')->restrictOnDelete();
            $table->foreignUlid('to_study_program_id')->constrained('study_programs')->restrictOnDelete();
            $table->text('reason');
            $table->string('status')->default('proposed');
            $table->foreignUlid('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignUlid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('reject_reason')->nullable();
            $table->foreignUlid('new_enrollment_id')->nullable()->constrained('student_enrollments')->nullOnDelete();
            $table->timestamps();
            $table->index(['student_enrollment_id', 'status']);
        });

        Schema::create('private_files', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUlid('student_enrollment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('label');
            $table->string('path');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('visibility')->default('private');
            $table->timestamps();
            $table->index(['owner_id', 'visibility']);
        });

        Schema::create('invoice_installments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_invoice_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('sequence');
            $table->decimal('amount', 15, 2);
            $table->date('due_on');
            $table->string('status')->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
            $table->unique(['student_invoice_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_installments');
        Schema::dropIfExists('private_files');
        Schema::dropIfExists('student_transfers');
    }
};
