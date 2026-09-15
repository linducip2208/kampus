<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizational_units', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('parent_id')->nullable()->constrained('organizational_units')->nullOnDelete();
            $table->string('name');
            $table->string('code');
            $table->string('kind')->default('unit');
            $table->foreignUlid('head_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->unique(['university_id', 'code']);
            $table->index(['university_id', 'status']);
        });

        Schema::create('positions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('unit_id')->nullable()->constrained('organizational_units')->nullOnDelete();
            $table->string('name');
            $table->string('code');
            $table->string('level')->default('staff');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['university_id', 'code']);
        });

        Schema::create('employment_contracts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('employee_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('position_id')->nullable()->constrained()->nullOnDelete();
            $table->string('contract_number')->unique();
            $table->string('type')->default('tetap');
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->decimal('salary', 18, 2)->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
            $table->index(['employee_id', 'status']);
        });

        Schema::create('employee_attendances', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('employee_id')->constrained()->restrictOnDelete();
            $table->date('date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->string('status')->default('present');
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['employee_id', 'date']);
        });

        Schema::create('employee_leaves', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('employee_id')->constrained()->restrictOnDelete();
            $table->string('kind')->default('annual');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->text('reason');
            $table->string('status')->default('proposed');
            $table->foreignUlid('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignUlid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('reject_reason')->nullable();
            $table->timestamps();
            $table->index(['employee_id', 'status']);
        });

        Schema::create('education_histories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('employee_id')->constrained()->restrictOnDelete();
            $table->string('level');
            $table->string('institution');
            $table->string('major')->nullable();
            $table->unsignedSmallInteger('graduation_year');
            $table->string('certificate_number')->nullable();
            $table->timestamps();
        });

        Schema::create('certifications', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('employee_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('issuer');
            $table->date('issued_on');
            $table->date('expires_on')->nullable();
            $table->string('credential_number')->nullable();
            $table->timestamps();
        });

        Schema::create('lecturer_workloads', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('lecturer_profile_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('semester_id')->constrained()->restrictOnDelete();
            $table->decimal('target_sks', 5, 2)->default(12);
            $table->string('status')->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->foreignUlid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->text('reject_reason')->nullable();
            $table->timestamps();
            $table->unique(['lecturer_profile_id', 'semester_id']);
            $table->index(['semester_id', 'status']);
        });

        Schema::create('workload_activities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('lecturer_workload_id')->constrained()->cascadeOnDelete();
            $table->string('category');
            $table->string('title');
            $table->decimal('sks', 5, 2)->default(0);
            $table->string('evidence_path')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['lecturer_workload_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workload_activities');
        Schema::dropIfExists('lecturer_workloads');
        Schema::dropIfExists('certifications');
        Schema::dropIfExists('education_histories');
        Schema::dropIfExists('employee_leaves');
        Schema::dropIfExists('employee_attendances');
        Schema::dropIfExists('employment_contracts');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('organizational_units');
    }
};
