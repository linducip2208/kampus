<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scholarship_periods', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('scholarship_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('semester_id')->constrained()->restrictOnDelete();
            $table->date('opens_on');
            $table->date('closes_on');
            $table->unsignedInteger('quota')->default(0);
            $table->string('status')->default('open');
            $table->timestamps();
            $table->unique(['scholarship_id', 'semester_id']);
        });

        Schema::create('thesis_guidances', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('thesis_proposal_id')->constrained()->cascadeOnDelete();
            $table->text('student_note')->nullable();
            $table->text('supervisor_note')->nullable();
            $table->string('attachment_path')->nullable();
            $table->timestamp('guided_at')->nullable();
            $table->timestamps();
            $table->index(['thesis_proposal_id', 'created_at']);
        });

        Schema::create('thesis_examiners', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('thesis_defense_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('lecturer_profile_id')->constrained()->restrictOnDelete();
            $table->string('role')->default('examiner');
            $table->decimal('score', 5, 2)->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['thesis_defense_id', 'lecturer_profile_id']);
        });

        Schema::create('thesis_revisions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('thesis_defense_id')->constrained()->cascadeOnDelete();
            $table->text('item');
            $table->boolean('is_done')->default(false);
            $table->foreignUlid('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('graduation_periods', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('semester_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->date('opens_on');
            $table->date('closes_on');
            $table->date('ceremony_on')->nullable();
            $table->string('status')->default('open');
            $table->timestamps();
            $table->index(['university_id', 'status']);
        });

        Schema::create('graduation_clearances', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('graduation_id')->constrained()->cascadeOnDelete();
            $table->string('kind');
            $table->string('status')->default('pending');
            $table->foreignUlid('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['graduation_id', 'kind']);
        });

        Schema::create('tracer_sections', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('tracer_questions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tracer_section_id')->constrained()->cascadeOnDelete();
            $table->text('prompt');
            $table->string('type')->default('text');
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->boolean('is_required')->default(false);
            $table->timestamps();
        });

        Schema::create('tracer_options', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tracer_question_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('value');
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->timestamps();
        });

        Schema::create('tracer_responses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('tracer_survey_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('tracer_question_id')->constrained()->restrictOnDelete();
            $table->text('answer')->nullable();
            $table->timestamps();
            $table->unique(['tracer_survey_id', 'tracer_question_id']);
        });

        Schema::create('companies', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('industry')->nullable();
            $table->string('website')->nullable();
            $table->boolean('is_partner')->default(false);
            $table->timestamps();
            $table->index(['university_id', 'is_partner']);
        });

        Schema::create('job_vacancies', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('company_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('closes_on')->nullable();
            $table->string('status')->default('open');
            $table->timestamps();
            $table->index(['company_id', 'status']);
        });

        Schema::create('job_applications', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('job_vacancy_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('alumni_profile_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('submitted');
            $table->timestamps();
            $table->unique(['job_vacancy_id', 'alumni_profile_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('job_vacancies');
        Schema::dropIfExists('companies');
        Schema::dropIfExists('tracer_responses');
        Schema::dropIfExists('tracer_options');
        Schema::dropIfExists('tracer_questions');
        Schema::dropIfExists('tracer_sections');
        Schema::dropIfExists('graduation_clearances');
        Schema::dropIfExists('graduation_periods');
        Schema::dropIfExists('thesis_revisions');
        Schema::dropIfExists('thesis_examiners');
        Schema::dropIfExists('thesis_guidances');
        Schema::dropIfExists('scholarship_periods');
    }
};
