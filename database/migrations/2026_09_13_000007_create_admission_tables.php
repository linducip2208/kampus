<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admission_paths', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('code');
            $table->decimal('passing_grade', 5, 2)->nullable();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['university_id', 'code']);
        });

        Schema::create('applicants', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('admission_path_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('converted_student_profile_id')->nullable()->constrained('student_profiles')->nullOnDelete();
            $table->string('registration_number')->unique();
            $table->string('national_id')->nullable()->index();
            $table->string('nisn')->nullable()->index();
            $table->string('name');
            $table->string('birth_place')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('gender')->nullable();
            $table->string('email')->index();
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->text('address')->nullable();
            $table->string('previous_school')->nullable();
            $table->unsignedSmallInteger('graduation_year')->nullable();
            $table->decimal('school_score', 5, 2)->nullable();
            $table->decimal('selection_score', 5, 2)->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('passed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['university_id', 'status']);
        });

        Schema::create('applicant_program_choices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('applicant_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('study_program_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('preference');
            $table->string('status')->default('submitted');
            $table->timestamps();
            $table->unique(['applicant_id', 'study_program_id']);
            $table->unique(['applicant_id', 'preference']);
        });

        Schema::create('applicant_status_histories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('applicant_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('reason')->nullable();
            $table->foreignUlid('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at');
            $table->timestamps();
            $table->index(['applicant_id', 'changed_at']);
        });

        Schema::create('admission_exams', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('applicant_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->timestamp('scheduled_at')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->string('status')->default('scheduled');
            $table->timestamps();
        });

        Schema::create('applicant_interviews', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('applicant_id')->constrained()->cascadeOnDelete();
            $table->timestamp('scheduled_at')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->text('notes')->nullable();
            $table->string('status')->default('scheduled');
            $table->timestamps();
        });

        Schema::create('applicant_re_registrations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('applicant_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status')->default('pending');
            $table->foreignUlid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicant_re_registrations');
        Schema::dropIfExists('applicant_interviews');
        Schema::dropIfExists('admission_exams');
        Schema::dropIfExists('applicant_status_histories');
        Schema::dropIfExists('applicant_program_choices');
        Schema::dropIfExists('applicants');
        Schema::dropIfExists('admission_paths');
    }
};
