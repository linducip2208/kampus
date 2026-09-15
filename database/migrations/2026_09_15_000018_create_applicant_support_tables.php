<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applicant_documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('applicant_id')->constrained()->cascadeOnDelete();
            $table->string('kind');
            $table->string('label');
            $table->string('file_path');
            $table->string('status')->default('pending');
            $table->foreignUlid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
            $table->index(['applicant_id', 'status']);
        });

        Schema::create('applicant_payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('applicant_id')->constrained()->cascadeOnDelete();
            $table->string('reference_number')->unique();
            $table->decimal('amount', 15, 2);
            $table->string('method')->default('transfer');
            $table->string('proof_path')->nullable();
            $table->string('status')->default('submitted');
            $table->foreignUlid('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
            $table->index(['applicant_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applicant_payments');
        Schema::dropIfExists('applicant_documents');
    }
};
