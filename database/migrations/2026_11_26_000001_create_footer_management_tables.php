<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Enforce the singleton even for writes outside Eloquent.
        DB::statement('CREATE TABLE footer_settings (id INTEGER NOT NULL PRIMARY KEY CHECK (id = 1), logo_path VARCHAR(255) NULL, logo_alt VARCHAR(255) NULL, description TEXT NULL, tagline VARCHAR(255) NULL, contact_email VARCHAR(255) NULL, contact_phone VARCHAR(50) NULL, whatsapp_number VARCHAR(50) NULL, office_address TEXT NULL, google_maps_url VARCHAR(2048) NULL, copyright_text TEXT NULL, copyright_year INTEGER NULL, show_copyright BOOLEAN NOT NULL DEFAULT 1, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL)');
        Schema::create('footer_sections', function (Blueprint $table) {
            $table->id();
            $table->integer('footer_setting_id');
            $table->foreign('footer_setting_id')->references('id')->on('footer_settings')->cascadeOnDelete();
            $table->string('title');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        Schema::create('footer_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('footer_section_id')->constrained()->cascadeOnDelete();
            $table->string('label');
            $table->string('url', 2048);
            $table->boolean('open_new_tab')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
        Schema::create('footer_social_links', function (Blueprint $table) {
            $table->id();
            $table->integer('footer_setting_id');
            $table->foreign('footer_setting_id')->references('id')->on('footer_settings')->cascadeOnDelete();
            $table->string('platform');
            $table->string('url', 2048)->nullable();
            $table->string('icon', 100);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('status')->default(false);
            $table->timestamps();
            $table->unique(['footer_setting_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('footer_social_links');
        Schema::dropIfExists('footer_links');
        Schema::dropIfExists('footer_sections');
        Schema::dropIfExists('footer_settings');
    }
};
