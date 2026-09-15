<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->unsignedTinyInteger('max_attempts')->default(3)->after('allow_late');
        });
        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->unsignedTinyInteger('attempts_count')->default(0)->after('is_late');
            $table->foreignUlid('graded_by')->nullable()->after('score')->constrained('users')->restrictOnDelete();
            $table->timestamp('graded_at')->nullable()->after('graded_by');
        });
    }

    public function down(): void
    {
        Schema::table('assignment_submissions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('graded_by');
            $table->dropColumn(['attempts_count', 'graded_at']);
        });
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn('max_attempts');
        });
    }
};
