<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_page_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('page_title');
            $table->string('page_subtitle')->nullable();
            $table->string('main_heading');
            $table->text('description')->nullable();
            $table->string('banner_image')->nullable();
            $table->string('banner_alt_text')->nullable();
            $table->string('contact_email');
            $table->string('contact_phone', 50)->nullable();
            $table->string('whatsapp_number', 50)->nullable();
            $table->text('office_address')->nullable();
            $table->string('google_maps_url', 2048)->nullable();
            $table->text('business_hours')->nullable();
            $table->string('working_days')->nullable();
            $table->text('additional_information')->nullable();
            $table->string('facebook_url', 2048)->nullable();
            $table->string('instagram_url', 2048)->nullable();
            $table->string('linkedin_url', 2048)->nullable();
            $table->string('youtube_url', 2048)->nullable();
            $table->string('contact_form_heading');
            $table->text('contact_form_description')->nullable();
            $table->string('submit_button_text', 100);
            $table->text('success_message');
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->text('meta_keywords')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_page_settings');
    }
};
