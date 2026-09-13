<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void { Schema::table('student_enrollments', fn (Blueprint $table) => $table->string('admission_type')->nullable()->after('curriculum_id')); }
    public function down(): void { Schema::table('student_enrollments', fn (Blueprint $table) => $table->dropColumn('admission_type')); }
};
