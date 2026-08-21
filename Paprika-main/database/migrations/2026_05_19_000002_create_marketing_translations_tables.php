<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banner_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('banner_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8)->index();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('button_text')->nullable();
            $table->timestamps();
            $table->unique(['banner_id', 'locale']);
        });

        Schema::create('promotion_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('promotion_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8)->index();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('badge')->nullable();
            $table->string('button_text')->nullable();
            $table->timestamps();
            $table->unique(['promotion_id', 'locale']);
        });

        Schema::create('testimonial_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('testimonial_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8)->index();
            $table->string('role')->nullable();
            $table->text('content');
            $table->timestamps();
            $table->unique(['testimonial_id', 'locale']);
        });

        Schema::create('gallery_image_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('gallery_image_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8)->index();
            $table->string('title');
            $table->string('slug', 180);
            $table->text('description')->nullable();
            $table->string('alt_text')->nullable();
            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->timestamps();
            $table->unique(['gallery_image_id', 'locale']);
            $table->unique(['locale', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gallery_image_translations');
        Schema::dropIfExists('testimonial_translations');
        Schema::dropIfExists('promotion_translations');
        Schema::dropIfExists('banner_translations');
    }
};
