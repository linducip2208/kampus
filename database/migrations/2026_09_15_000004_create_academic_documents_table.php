<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('student_enrollment_id')->constrained()->restrictOnDelete();
            $table->string('document_number');
            $table->string('type');
            $table->string('locale', 5)->default('id');
            $table->string('verification_token', 64)->unique();
            $table->string('payload_checksum', 64);
            $table->json('snapshot');
            $table->string('status')->default('issued')->index();
            $table->foreignUlid('issued_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('issued_at');
            $table->foreignUlid('revoked_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('revoked_at')->nullable();
            $table->text('revocation_reason')->nullable();
            $table->timestamps();

            $table->unique(['university_id', 'document_number']);
            $table->index(['student_enrollment_id', 'type', 'status'], 'academic_document_lookup');
            $table->index(['student_enrollment_id', 'payload_checksum'], 'academic_document_checksum');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_documents');
    }
};
