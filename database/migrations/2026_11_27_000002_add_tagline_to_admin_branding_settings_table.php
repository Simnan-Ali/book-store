<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_branding_settings', function (Blueprint $table): void {
            $table->string('tagline', 100)->nullable()->default('BOOKS ADMIN');
        });
    }

    public function down(): void
    {
        Schema::table('admin_branding_settings', function (Blueprint $table): void {
            $table->dropColumn('tagline');
        });
    }
};
