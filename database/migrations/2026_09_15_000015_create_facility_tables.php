<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('buildings', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('campus_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('code');
            $table->unsignedTinyInteger('floors')->default(1);
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['campus_id', 'code']);
            $table->index(['campus_id', 'status']);
        });

        Schema::create('rooms', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('building_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('code');
            $table->string('kind')->default('classroom');
            $table->unsignedSmallInteger('capacity')->default(0);
            $table->unsignedTinyInteger('floor')->default(1);
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['building_id', 'code']);
            $table->index(['building_id', 'status', 'kind']);
        });

        Schema::create('laboratories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('department_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('room_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('code');
            $table->string('kind')->default('computer');
            $table->foreignUlid('head_id')->nullable()->constrained('lecturer_profiles')->nullOnDelete();
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['department_id', 'code']);
            $table->index(['department_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laboratories');
        Schema::dropIfExists('rooms');
        Schema::dropIfExists('buildings');
    }
};
