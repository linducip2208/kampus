<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('library_books', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('author')->nullable();
            $table->string('isbn')->nullable();
            $table->string('publisher')->nullable();
            $table->unsignedSmallInteger('published_year')->nullable();
            $table->string('category')->default('general');
            $table->string('shelf')->nullable();
            $table->unsignedInteger('copies_total')->default(1);
            $table->unsignedInteger('copies_available')->default(1);
            $table->timestamps();
            $table->unique(['university_id', 'isbn'], 'library_isbn_unique');
            $table->index(['university_id', 'category']);
        });

        Schema::create('library_loans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('library_book_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('student_enrollment_id')->constrained()->restrictOnDelete();
            $table->timestamp('borrowed_at');
            $table->timestamp('due_at');
            $table->timestamp('returned_at')->nullable();
            $table->string('status')->default('borrowed');
            $table->decimal('fine_amount', 15, 2)->default(0);
            $table->timestamps();
            $table->index(['student_enrollment_id', 'status']);
        });

        Schema::create('research_projects', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('scheme')->default('internal');
            $table->unsignedSmallInteger('year');
            $table->foreignUlid('lead_id')->nullable()->constrained('lecturer_profiles')->nullOnDelete();
            $table->string('status')->default('proposed');
            $table->decimal('budget', 18, 2)->default(0);
            $table->timestamps();
            $table->index(['university_id', 'status', 'year']);
        });

        Schema::create('research_members', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('research_project_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('lecturer_profile_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('student_enrollment_id')->nullable()->constrained()->nullOnDelete();
            $table->string('role')->default('member');
            $table->timestamps();
            $table->index(['research_project_id']);
        });

        Schema::create('community_services', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('location')->nullable();
            $table->unsignedSmallInteger('year');
            $table->foreignUlid('lead_id')->nullable()->constrained('lecturer_profiles')->nullOnDelete();
            $table->string('status')->default('proposed');
            $table->decimal('budget', 18, 2)->default(0);
            $table->timestamps();
            $table->index(['university_id', 'status', 'year']);
        });

        Schema::create('student_organizations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('code');
            $table->string('kind')->default('ukm');
            $table->foreignUlid('advisor_id')->nullable()->constrained('lecturer_profiles')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['university_id', 'code']);
        });

        Schema::create('student_organization_members', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_organization_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('student_enrollment_id')->constrained()->restrictOnDelete();
            $table->string('role')->default('member');
            $table->timestamp('joined_at');
            $table->timestamp('left_at')->nullable();
            $table->timestamps();
            $table->unique(['student_organization_id', 'student_enrollment_id'], 'org_member_unique');
        });

        Schema::create('student_activities', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_organization_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->string('venue')->nullable();
            $table->timestamp('starts_at');
            $table->timestamp('ends_at');
            $table->string('status')->default('proposed');
            $table->foreignUlid('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignUlid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['student_organization_id', 'status']);
        });

        Schema::create('mbkm_programs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('kind')->default('magang');
            $table->string('partner')->nullable();
            $table->unsignedInteger('quota')->default(0);
            $table->string('status')->default('open');
            $table->timestamps();
            $table->index(['university_id', 'status']);
        });

        Schema::create('mbkm_registrations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('mbkm_program_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('student_enrollment_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('proposed');
            $table->unsignedInteger('credits_recognized')->default(0);
            $table->foreignUlid('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignUlid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
            $table->unique(['mbkm_program_id', 'student_enrollment_id'], 'mbkm_unique');
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mbkm_registrations');
        Schema::dropIfExists('mbkm_programs');
        Schema::dropIfExists('student_activities');
        Schema::dropIfExists('student_organization_members');
        Schema::dropIfExists('student_organizations');
        Schema::dropIfExists('community_services');
        Schema::dropIfExists('research_members');
        Schema::dropIfExists('research_projects');
        Schema::dropIfExists('library_loans');
        Schema::dropIfExists('library_books');
    }
};
