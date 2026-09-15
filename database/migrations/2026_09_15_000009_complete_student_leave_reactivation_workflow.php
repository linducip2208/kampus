<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_leave_requests', function (Blueprint $table) {
            $table->foreignUlid('processed_by')->nullable()->after('approved_at')->constrained('users')->restrictOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('processed_by');
            $table->text('rejection_reason')->nullable()->after('rejected_at');
        });

        Schema::create('student_reactivation_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('semester_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('requested_by')->constrained('users')->restrictOnDelete();
            $table->string('status')->default('submitted');
            $table->text('reason');
            $table->timestamp('submitted_at');
            $table->timestamp('approved_at')->nullable();
            $table->foreignUlid('processed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->unique(['student_enrollment_id', 'semester_id']);
            $table->index(['status', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_reactivation_requests');
        Schema::table('student_leave_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('processed_by');
            $table->dropColumn(['rejected_at', 'rejection_reason']);
        });
    }
};
