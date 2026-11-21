<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('isbn')->nullable()->unique();
            $table->string('sku')->unique();
            $table->string('short_description')->nullable();
            $table->longText('description')->nullable();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('brand_id')->constrained()->restrictOnDelete();
            $table->string('author');
            $table->string('language')->nullable();
            $table->string('edition')->nullable();
            $table->date('published_at')->nullable();
            $table->decimal('regular_price', 10, 2);
            $table->decimal('selling_price', 10, 2);
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->unsignedInteger('low_stock_alert')->default(5);
            $table->string('main_image')->nullable();
            $table->unsignedSmallInteger('pages')->nullable();
            $table->string('format', 30)->default('Paperback');
            $table->decimal('weight', 8, 2)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_best_seller')->default(false);
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['category_id', 'subcategory_id', 'brand_id']);
            $table->index(['stock_quantity', 'low_stock_alert']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
