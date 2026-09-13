<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_leave_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('semester_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('requested_by')->constrained('users')->restrictOnDelete();
            $table->string('status')->default('submitted');
            $table->text('reason');
            $table->string('attachment_path')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->unique(['student_enrollment_id', 'semester_id']);
            $table->index(['status', 'submitted_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('student_leave_requests'); }
};
