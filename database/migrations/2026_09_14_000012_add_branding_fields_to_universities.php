<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('universities', function (Blueprint $table) {
            $table->string('logo_dark_path')->nullable()->after('logo_path');
            $table->string('favicon_path')->nullable()->after('logo_dark_path');
            $table->string('primary_color', 20)->nullable()->after('favicon_path');
            $table->string('secondary_color', 20)->nullable()->after('primary_color');
            $table->text('address')->nullable()->after('secondary_color');
            $table->string('letterhead_path')->nullable()->after('website');
            $table->text('document_footer')->nullable()->after('letterhead_path');
            $table->string('signature_path')->nullable()->after('document_footer');
            $table->string('document_logo_path')->nullable()->after('signature_path');
        });
    }

    public function down(): void
    {
        Schema::table('universities', function (Blueprint $table) {
            $table->dropColumn([
                'logo_dark_path', 'favicon_path', 'primary_color', 'secondary_color',
                'address', 'letterhead_path', 'document_footer', 'signature_path',
                'document_logo_path',
            ]);
        });
    }
};
