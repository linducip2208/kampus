<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_modules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('class_section_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->unsignedSmallInteger('position')->default(1);
            $table->string('status')->default('draft');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['class_section_id', 'position']);
        });
        Schema::create('course_contents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('course_module_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->longText('body')->nullable();
            $table->string('file_path')->nullable();
            $table->string('external_url')->nullable();
            $table->unsignedSmallInteger('position')->default(1);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['course_module_id', 'position']);
        });
        Schema::create('assignments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('class_section_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->longText('instructions')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('opens_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->boolean('allow_late')->default(false);
            $table->decimal('max_score', 5, 2)->default(100);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['class_section_id', 'status', 'due_at']);
        });
        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('assignment_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('student_enrollment_id')->constrained()->restrictOnDelete();
            $table->string('file_path')->nullable();
            $table->longText('answer_text')->nullable();
            $table->timestamp('submitted_at');
            $table->boolean('is_late')->default(false);
            $table->decimal('score', 5, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->string('status')->default('submitted');
            $table->timestamps();
            $table->unique(['assignment_id', 'student_enrollment_id']);
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('course_contents');
        Schema::dropIfExists('course_modules');
    }
};
