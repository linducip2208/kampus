<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->json('question_order')->nullable()->after('attempt_number');
            $table->json('option_order')->nullable()->after('question_order');
            $table->timestamp('expires_at')->nullable()->after('started_at')->index();
        });
        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->foreignUlid('graded_by')->nullable()->after('score')->constrained('users')->restrictOnDelete();
            $table->timestamp('graded_at')->nullable()->after('graded_by');
        });
    }

    public function down(): void
    {
        Schema::table('quiz_answers', function (Blueprint $table) {
            $table->dropConstrainedForeignId('graded_by');
            $table->dropColumn('graded_at');
        });
        Schema::table('quiz_attempts', function (Blueprint $table) {
            $table->dropIndex(['expires_at']);
            $table->dropColumn(['question_order', 'option_order', 'expires_at']);
        });
    }
};
