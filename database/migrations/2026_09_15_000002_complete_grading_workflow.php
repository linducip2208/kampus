<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_component_scores', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('study_plan_item_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('grading_component_id')->constrained()->restrictOnDelete();
            $table->decimal('score', 5, 2);
            $table->foreignUlid('graded_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('feedback')->nullable();
            $table->timestamps();
            $table->unique(['study_plan_item_id', 'grading_component_id'], 'student_component_score_unique');
        });

        Schema::table('student_grades', function (Blueprint $table) {
            $table->foreignUlid('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignUlid('published_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
        });

        Schema::create('grade_revision_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('student_grade_id')->constrained()->restrictOnDelete();
            $table->decimal('old_score', 5, 2);
            $table->decimal('new_score', 5, 2);
            $table->text('reason');
            $table->string('status')->default('pending')->index();
            $table->foreignUlid('requested_by')->constrained('users')->restrictOnDelete();
            $table->foreignUlid('approved_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->index(['student_grade_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_revision_requests');
        Schema::table('student_grades', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('published_by');
            $table->dropColumn(['submitted_at', 'approved_at', 'published_at']);
        });
        Schema::dropIfExists('student_component_scores');
    }
};
