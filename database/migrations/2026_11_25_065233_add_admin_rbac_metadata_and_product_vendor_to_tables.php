<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->string('display_name')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_protected')->default(false);
        });

        Schema::table('permissions', function (Blueprint $table): void {
            $table->string('display_name')->nullable();
            $table->string('module')->nullable()->index();
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->foreignId('vendor_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->dropForeign(['vendor_id']);
            $table->dropColumn('vendor_id');
        });

        Schema::table('permissions', function (Blueprint $table): void {
            $table->dropIndex(['module']);
            $table->dropColumn(['display_name', 'module', 'description', 'is_system']);
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->dropColumn(['display_name', 'description', 'is_active', 'is_protected']);
        });
    }
};
