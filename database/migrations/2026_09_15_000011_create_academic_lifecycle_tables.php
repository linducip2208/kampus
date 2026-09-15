<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scholarships', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('code');
            $table->string('kind')->default('academic');
            $table->decimal('amount', 15, 2);
            $table->unsignedInteger('quota')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['university_id', 'code']);
            $table->index(['university_id', 'is_active']);
        });

        Schema::create('scholarship_awards', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('scholarship_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('student_enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('semester_id')->nullable()->constrained()->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('status')->default('proposed');
            $table->foreignUlid('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignUlid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('reject_reason')->nullable();
            $table->timestamps();
            $table->unique(['scholarship_id', 'student_enrollment_id', 'semester_id'], 'scholarship_award_unique');
            $table->index(['status', 'created_at']);
        });

        Schema::create('thesis_proposals', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('advisor_id')->nullable()->constrained('lecturer_profiles')->nullOnDelete();
            $table->string('title');
            $table->text('abstract')->nullable();
            $table->string('status')->default('submitted');
            $table->foreignUlid('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignUlid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('reject_reason')->nullable();
            $table->timestamps();
            $table->index(['student_enrollment_id', 'status']);
        });

        Schema::create('thesis_defenses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('thesis_proposal_id')->constrained()->restrictOnDelete();
            $table->timestamp('scheduled_at');
            $table->string('venue')->nullable();
            $table->string('status')->default('scheduled');
            $table->decimal('score', 5, 2)->nullable();
            $table->string('grade')->nullable();
            $table->foreignUlid('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();
            $table->index(['thesis_proposal_id', 'status']);
        });

        Schema::create('graduations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('semester_id')->constrained()->restrictOnDelete();
            $table->decimal('gpa', 4, 3)->default(0);
            $table->unsignedInteger('total_sks')->default(0);
            $table->string('predicate')->nullable();
            $table->string('status')->default('proposed');
            $table->string('certificate_number')->nullable()->unique();
            $table->foreignUlid('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignUlid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('graduated_at')->nullable();
            $table->timestamps();
            $table->unique(['student_enrollment_id', 'semester_id'], 'graduation_unique');
            $table->index(['status', 'graduated_at']);
        });

        Schema::create('alumni_profiles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_enrollment_id')->constrained()->restrictOnDelete()->unique();
            $table->foreignUlid('graduation_id')->constrained()->restrictOnDelete();
            $table->string('employment_status')->default('unknown');
            $table->string('company')->nullable();
            $table->string('position')->nullable();
            $table->timestamp('started_work_at')->nullable();
            $table->timestamps();
            $table->index(['employment_status']);
        });

        Schema::create('tracer_surveys', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('alumni_profile_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('graduation_year');
            $table->string('employment_status');
            $table->string('salary_range')->nullable();
            $table->string('relevance')->nullable();
            $table->unsignedTinyInteger('satisfaction')->nullable();
            $table->timestamp('filled_at');
            $table->timestamps();
            $table->unique(['alumni_profile_id', 'graduation_year'], 'tracer_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tracer_surveys');
        Schema::dropIfExists('alumni_profiles');
        Schema::dropIfExists('graduations');
        Schema::dropIfExists('thesis_defenses');
        Schema::dropIfExists('thesis_proposals');
        Schema::dropIfExists('scholarship_awards');
        Schema::dropIfExists('scholarships');
    }
};
