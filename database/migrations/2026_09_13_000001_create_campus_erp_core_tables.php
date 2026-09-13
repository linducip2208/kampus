<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name')->unique();
            $table->string('label');
            $table->string('scope_type')->default('university');
            $table->json('permissions')->nullable();
            $table->timestamps();
        });

        Schema::create('universities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('short_name')->nullable();
            $table->string('code')->unique();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('logo_path')->nullable();
            $table->string('timezone')->default('Asia/Jakarta');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('campuses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('code');
            $table->string('address')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['university_id', 'code']);
        });

        Schema::create('faculties', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('code');
            $table->foreignUlid('dean_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['university_id', 'code']);
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('faculty_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('code');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['faculty_id', 'code']);
        });

        Schema::create('study_programs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('department_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('code');
            $table->string('level')->default('S1');
            $table->string('degree')->nullable();
            $table->string('status')->default('active');
            $table->string('accreditation')->nullable();
            $table->unsignedInteger('capacity')->default(0);
            $table->foreignUlid('head_lecturer_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['department_id', 'code']);
            $table->index(['level', 'status']);
        });

        Schema::create('role_user', function (Blueprint $table) {
            $table->foreignUlid('role_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('university_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('campus_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('faculty_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('study_program_id')->nullable()->constrained()->nullOnDelete();
            $table->primary(['role_id', 'user_id']);
        });

        Schema::create('academic_years', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('start_year');
            $table->unsignedSmallInteger('end_year');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->unique(['university_id', 'name']);
        });

        Schema::create('semesters', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('academic_year_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('code');
            $table->string('term')->default('odd');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->boolean('is_active')->default(false);
            $table->timestamps();
            $table->unique(['academic_year_id', 'code']);
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->string('employee_number')->unique();
            $table->string('full_name');
            $table->string('employment_type')->default('permanent');
            $table->string('phone')->nullable();
            $table->date('joined_on')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['full_name', 'employee_number']);
        });

        Schema::create('lecturer_profiles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('employee_id')->unique()->constrained()->restrictOnDelete();
            $table->string('nidn')->nullable()->unique();
            $table->string('academic_rank')->nullable();
            $table->string('specialization')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('student_profiles', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('student_number')->unique();
            $table->string('full_name');
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable();
            $table->string('national_id')->nullable()->index();
            $table->string('gender')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('address')->nullable();
            $table->string('photo_path')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['full_name', 'student_number']);
        });

        Schema::create('curricula', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('study_program_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->unsignedSmallInteger('year');
            $table->unsignedSmallInteger('minimum_credits')->default(144);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['study_program_id', 'name']);
        });

        Schema::create('courses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->string('code');
            $table->string('name');
            $table->unsignedTinyInteger('theory_credits')->default(0);
            $table->unsignedTinyInteger('practical_credits')->default(0);
            $table->unsignedTinyInteger('recommended_term')->nullable();
            $table->boolean('is_mandatory')->default(true);
            $table->string('minimum_grade')->default('D');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['university_id', 'code']);
            $table->index('name');
        });

        Schema::create('curriculum_courses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('curriculum_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('course_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('term');
            $table->boolean('is_mandatory')->default(true);
            $table->timestamps();
            $table->unique(['curriculum_id', 'course_id']);
        });

        Schema::create('course_prerequisites', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('course_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('prerequisite_course_id')->constrained('courses')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['course_id', 'prerequisite_course_id']);
        });

        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_profile_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('study_program_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('curriculum_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('advisor_id')->nullable()->constrained('lecturer_profiles')->nullOnDelete();
            $table->unsignedSmallInteger('cohort');
            $table->string('status')->default('active');
            $table->date('enrolled_on')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['status', 'cohort']);
        });

        Schema::create('student_status_histories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_enrollment_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('reason')->nullable();
            $table->foreignUlid('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('changed_at');
            $table->timestamps();
        });

        Schema::create('course_offerings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('semester_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('course_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->unique(['semester_id', 'course_id']);
        });

        Schema::create('class_sections', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('course_offering_id')->constrained()->restrictOnDelete();
            $table->string('code');
            $table->unsignedInteger('capacity')->default(40);
            $table->string('room')->nullable();
            $table->string('mode')->default('offline');
            $table->timestamps();
            $table->unique(['course_offering_id', 'code']);
        });

        Schema::create('class_lecturers', function (Blueprint $table) {
            $table->foreignUlid('class_section_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('lecturer_profile_id')->constrained()->restrictOnDelete();
            $table->boolean('is_primary')->default(false);
            $table->primary(['class_section_id', 'lecturer_profile_id']);
        });

        Schema::create('class_schedules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('class_section_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('starts_at');
            $table->time('ends_at');
            $table->string('room')->nullable();
            $table->timestamps();
        });

        Schema::create('lecture_meetings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('class_section_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('meeting_number');
            $table->date('meeting_date');
            $table->string('topic')->nullable();
            $table->string('mode')->default('offline');
            $table->string('status')->default('planned');
            $table->timestamps();
            $table->unique(['class_section_id', 'meeting_number']);
        });

        Schema::create('study_plans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('semester_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('draft');
            $table->unsignedTinyInteger('total_credits')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignUlid('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('advisor_note')->nullable();
            $table->timestamps();
            $table->unique(['student_enrollment_id', 'semester_id']);
        });

        Schema::create('study_plan_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('study_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('class_section_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('credits');
            $table->string('status')->default('selected');
            $table->timestamps();
            $table->unique(['study_plan_id', 'class_section_id']);
        });

        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('lecture_meeting_id')->constrained()->cascadeOnDelete();
            $table->string('method')->default('manual');
            $table->string('token_hash')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('student_attendances', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('attendance_session_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('student_enrollment_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('present');
            $table->timestamp('recorded_at');
            $table->string('ip_address', 45)->nullable();
            $table->string('device_hash')->nullable();
            $table->timestamps();
            $table->unique(['attendance_session_id', 'student_enrollment_id']);
        });

        Schema::create('grade_scales', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->string('grade');
            $table->decimal('minimum_score', 5, 2);
            $table->decimal('maximum_score', 5, 2);
            $table->decimal('grade_point', 3, 2);
            $table->timestamps();
            $table->unique(['university_id', 'grade']);
        });

        Schema::create('grading_components', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('class_section_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('weight', 5, 2);
            $table->timestamps();
        });

        Schema::create('student_grades', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('study_plan_item_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('grade_scale_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('final_score', 5, 2)->nullable();
            $table->string('status')->default('draft');
            $table->foreignUlid('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
            $table->unique('study_plan_item_id');
        });

        Schema::create('fee_types', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('code');
            $table->boolean('is_recurring')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['university_id', 'code']);
        });

        Schema::create('fee_structures', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('study_program_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('semester_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('fee_structure_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('fee_structure_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('fee_type_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });

        Schema::create('student_invoices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_enrollment_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('semester_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number')->unique();
            $table->decimal('total_amount', 15, 2)->default(0);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->string('status')->default('draft');
            $table->date('due_on')->nullable();
            $table->timestamps();
            $table->index(['status', 'due_on']);
        });

        Schema::create('invoice_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_invoice_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('fee_type_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_enrollment_id')->constrained()->restrictOnDelete();
            $table->string('payment_number')->unique();
            $table->decimal('amount', 15, 2);
            $table->string('method')->default('transfer');
            $table->string('status')->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->foreignUlid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['status', 'paid_at']);
        });

        Schema::create('payment_allocations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('payment_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('student_invoice_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->timestamps();
            $table->unique(['payment_id', 'student_invoice_id']);
        });

        Schema::create('approval_workflows', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('module');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('approval_steps', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('approval_workflow_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('step_order');
            $table->string('label');
            $table->string('role_name');
            $table->timestamps();
            $table->unique(['approval_workflow_id', 'step_order']);
        });

        Schema::create('approval_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('approval_workflow_id')->constrained()->restrictOnDelete();
            $table->morphs('approvable');
            $table->foreignUlid('requested_by')->constrained('users')->restrictOnDelete();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->index(['status', 'created_at']);
        });

        Schema::create('approval_actions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('approval_request_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('approval_step_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('acted_by')->constrained('users')->restrictOnDelete();
            $table->string('action');
            $table->text('note')->nullable();
            $table->timestamp('acted_at');
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event');
            $table->string('module');
            $table->string('entity_type');
            $table->string('entity_id');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['module', 'entity_type', 'entity_id']);
        });

        Schema::create('settings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('group');
            $table->string('key');
            $table->text('value')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->timestamps();
            $table->unique(['university_id', 'group', 'key']);
        });
    }

    public function down(): void
    {
        foreach ([
            'settings', 'audit_logs', 'approval_actions', 'approval_requests', 'approval_steps', 'approval_workflows',
            'payment_allocations', 'payments', 'invoice_items', 'student_invoices', 'fee_structure_items', 'fee_structures', 'fee_types',
            'student_grades', 'grading_components', 'grade_scales', 'student_attendances', 'attendance_sessions',
            'study_plan_items', 'study_plans', 'lecture_meetings', 'class_schedules', 'class_lecturers', 'class_sections', 'course_offerings',
            'student_status_histories', 'student_enrollments', 'course_prerequisites', 'curriculum_courses', 'courses', 'curricula',
            'student_profiles', 'lecturer_profiles', 'employees', 'semesters', 'academic_years', 'study_programs', 'departments', 'faculties', 'campuses',
            'universities', 'role_user', 'roles',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
