<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_sequences', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('university_id')->constrained()->restrictOnDelete();
            $table->string('key');
            $table->string('format');
            $table->unsignedBigInteger('next_value')->default(1);
            $table->timestamps();
            $table->unique(['university_id', 'key']);
        });
    }

    public function down(): void { Schema::dropIfExists('number_sequences'); }
};
