<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('course_announcements', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('class_section_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('author_id')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->longText('body');
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['class_section_id', 'published_at', 'is_pinned']);
        });

        Schema::create('discussions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('class_section_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('created_by')->constrained('users')->restrictOnDelete();
            $table->string('title');
            $table->longText('body');
            $table->string('status')->default('open');
            $table->timestamps();
            $table->softDeletes();
            $table->index(['class_section_id', 'status', 'updated_at']);
        });

        Schema::create('discussion_posts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('discussion_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('user_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('parent_id')->nullable()->constrained('discussion_posts')->nullOnDelete();
            $table->longText('body');
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['discussion_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discussion_posts');
        Schema::dropIfExists('discussions');
        Schema::dropIfExists('course_announcements');
    }
};
