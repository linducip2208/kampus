<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->string('status')->default('open')->index();
            $table->string('pin_hash')->nullable();
            $table->foreignUlid('opened_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('token_rotated_at')->nullable();
            $table->index(['lecture_meeting_id', 'status']);
        });

        Schema::table('student_attendances', function (Blueprint $table) {
            $table->string('method')->default('manual');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->foreignUlid('corrected_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->text('correction_reason')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('student_attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('corrected_by');
            $table->dropColumn(['method', 'latitude', 'longitude', 'correction_reason']);
        });
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->dropIndex(['lecture_meeting_id', 'status']);
            $table->dropConstrainedForeignId('opened_by');
            $table->dropColumn(['status', 'pin_hash', 'opened_at', 'closed_at', 'token_rotated_at']);
        });
    }
};
