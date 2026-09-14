<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_banks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['university_id', 'is_active']);
        });

        Schema::create('questions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('question_bank_id')->constrained()->restrictOnDelete();
            $table->string('type');
            $table->longText('prompt');
            $table->decimal('points', 5, 2)->default(1);
            $table->longText('explanation')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['question_bank_id', 'type', 'is_active']);
        });

        Schema::create('question_options', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('question_id')->constrained()->cascadeOnDelete();
            $table->text('option_text');
            $table->boolean('is_correct')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->timestamps();
            $table->unique(['question_id', 'sort_order']);
        });

        Schema::create('quizzes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('class_section_id')->constrained()->restrictOnDelete();
            $table->string('title');
            $table->longText('instructions')->nullable();
            $table->string('status')->default('draft');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->nullable();
            $table->unsignedTinyInteger('attempt_limit')->default(1);
            $table->boolean('randomize_questions')->default(false);
            $table->boolean('randomize_options')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['class_section_id', 'status', 'starts_at', 'ends_at']);
        });

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('question_id')->constrained()->restrictOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(1);
            $table->decimal('points', 5, 2)->nullable();
            $table->timestamps();
            $table->unique(['quiz_id', 'question_id']);
            $table->unique(['quiz_id', 'sort_order']);
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('quiz_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('student_enrollment_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('attempt_number');
            $table->timestamp('started_at');
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('score', 8, 2)->nullable();
            $table->string('status')->default('in_progress');
            $table->timestamps();
            $table->unique(['quiz_id', 'student_enrollment_id', 'attempt_number']);
            $table->index(['student_enrollment_id', 'status']);
        });

        Schema::create('quiz_answers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('quiz_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('question_id')->constrained()->restrictOnDelete();
            $table->json('selected_option_ids')->nullable();
            $table->longText('answer_text')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->decimal('score', 8, 2)->nullable();
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
            $table->unique(['quiz_attempt_id', 'question_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_answers');
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('quizzes');
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('questions');
        Schema::dropIfExists('question_banks');
    }
};
