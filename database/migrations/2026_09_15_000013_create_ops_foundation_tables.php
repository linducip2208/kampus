<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letter_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('code');
            $table->text('body');
            $table->json('variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['university_id', 'code']);
        });

        Schema::create('letter_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('letter_template_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('student_enrollment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('requested_by')->constrained('users')->restrictOnDelete();
            $table->text('purpose')->nullable();
            $table->json('payload')->nullable();
            $table->string('status')->default('proposed');
            $table->string('document_number')->nullable()->unique();
            $table->foreignUlid('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('issued_at')->nullable();
            $table->timestamps();
            $table->index(['status']);
        });

        Schema::create('assets', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('code');
            $table->string('category')->default('equipment');
            $table->string('location')->nullable();
            $table->string('condition')->default('good');
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_price', 18, 2)->default(0);
            $table->string('status')->default('available');
            $table->timestamps();
            $table->unique(['university_id', 'code']);
            $table->index(['university_id', 'status']);
        });

        Schema::create('asset_loans', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('asset_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('borrowed_by')->constrained('users')->restrictOnDelete();
            $table->text('purpose')->nullable();
            $table->timestamp('borrowed_at');
            $table->timestamp('due_at')->nullable();
            $table->timestamp('returned_at')->nullable();
            $table->string('status')->default('borrowed');
            $table->timestamps();
            $table->index(['asset_id', 'status']);
        });

        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->string('code');
            $table->string('name');
            $table->string('type');
            $table->foreignUlid('parent_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['university_id', 'code']);
        });

        Schema::create('journal_entries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->string('entry_number')->unique();
            $table->date('entry_date');
            $table->text('description');
            $table->string('status')->default('posted');
            $table->foreignUlid('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['university_id', 'entry_date']);
        });

        Schema::create('journal_lines', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('journal_entry_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('account_id')->constrained('chart_of_accounts')->restrictOnDelete();
            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);
            $table->string('memo')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->string('key');
            $table->string('channel')->default('database');
            $table->string('subject');
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['university_id', 'key', 'channel']);
        });

        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('user_id')->constrained()->cascadeOnDelete();
            $table->string('event');
            $table->string('channel')->default('database');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();
            $table->unique(['user_id', 'event', 'channel']);
        });

        Schema::create('integration_endpoints', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('kind');
            $table->string('base_url');
            $table->string('auth_type')->default('none');
            $table->text('credentials')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['university_id', 'kind']);
        });

        Schema::create('integration_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('integration_endpoint_id')->constrained()->restrictOnDelete();
            $table->string('event');
            $table->string('direction')->default('outbound');
            $table->json('payload')->nullable();
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->boolean('success')->default(false);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->timestamps();
            $table->index(['integration_endpoint_id', 'created_at']);
        });

        Schema::create('system_backups', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('filename')->unique();
            $table->string('disk')->default('local');
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->string('status')->default('completed');
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('system_health_checks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('check_name')->unique();
            $table->string('status')->default('pass');
            $table->json('details')->nullable();
            $table->timestamp('checked_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_health_checks');
        Schema::dropIfExists('system_backups');
        Schema::dropIfExists('integration_logs');
        Schema::dropIfExists('integration_endpoints');
        Schema::dropIfExists('notification_preferences');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
        Schema::dropIfExists('chart_of_accounts');
        Schema::dropIfExists('asset_loans');
        Schema::dropIfExists('assets');
        Schema::dropIfExists('letter_requests');
        Schema::dropIfExists('letter_templates');
    }
};
