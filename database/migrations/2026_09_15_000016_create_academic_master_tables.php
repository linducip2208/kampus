<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_calendars', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('academic_year_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->string('kind')->default('academic');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['university_id', 'starts_on', 'ends_on']);
        });

        Schema::create('holidays', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->date('date');
            $table->boolean('is_national')->default(false);
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['university_id', 'date']);
        });

        Schema::create('course_categories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('code');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['university_id', 'code']);
        });

        Schema::table('courses', function (Blueprint $table) {
            $table->foreignUlid('course_category_id')->nullable()->after('university_id')->constrained()->nullOnDelete();
            $table->string('course_type')->default('wajib')->after('course_category_id');
        });

        Schema::create('course_equivalences', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('old_course_id')->constrained('courses')->restrictOnDelete();
            $table->foreignUlid('new_course_id')->constrained('courses')->restrictOnDelete();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->unique(['old_course_id', 'new_course_id']);
        });

        Schema::create('academic_rules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->string('key');
            $table->json('value');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['university_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_rules');
        Schema::dropIfExists('course_equivalences');
        Schema::table('courses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('course_category_id');
            $table->dropColumn('course_type');
        });
        Schema::dropIfExists('course_categories');
        Schema::dropIfExists('holidays');
        Schema::dropIfExists('academic_calendars');
    }
};
