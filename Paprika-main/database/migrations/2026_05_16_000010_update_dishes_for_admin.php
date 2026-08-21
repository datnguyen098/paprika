<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dishes', function (Blueprint $table): void {
            $table->longText('content')->nullable()->after('description');
            $table->unsignedInteger('sale_price')->nullable()->after('price');
            $table->json('gallery')->nullable()->after('image');
            $table->boolean('is_active')->default(true)->after('is_featured')->index();
            $table->integer('sort_order')->default(0)->after('is_active')->index();
            $table->text('meta_keywords')->nullable()->after('meta_description');
        });
    }

    public function down(): void
    {
        Schema::table('dishes', function (Blueprint $table): void {
            $table->dropColumn(['content', 'sale_price', 'gallery', 'is_active', 'sort_order', 'meta_keywords']);
        });
    }
};
