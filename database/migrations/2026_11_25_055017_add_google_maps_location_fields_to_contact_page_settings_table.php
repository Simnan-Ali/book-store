<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('contact_page_settings', function (Blueprint $table): void {
            $table->string('map_location_name')->nullable();
            $table->string('google_maps_embed_url', 2048)->nullable();
            $table->decimal('map_latitude', 10, 7)->nullable();
            $table->decimal('map_longitude', 10, 7)->nullable();
            $table->boolean('show_map')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('contact_page_settings', function (Blueprint $table): void {
            $table->dropColumn([
                'map_location_name',
                'google_maps_embed_url',
                'map_latitude',
                'map_longitude',
                'show_map',
            ]);
        });
    }
};
