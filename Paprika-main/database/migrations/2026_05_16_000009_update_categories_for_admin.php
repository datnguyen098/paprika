<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table): void {
            $table->string('image')->nullable()->after('type');
            $table->integer('sort_order')->default(0)->after('image')->index();
            $table->boolean('is_active')->default(true)->after('sort_order')->index();
            $table->string('meta_title')->nullable()->after('is_active');
            $table->text('meta_description')->nullable()->after('meta_title');
        });

        DB::table('categories')->where('type', 'menu')->update(['type' => 'dish']);
        DB::table('categories')->where('type', 'blog')->update(['type' => 'post']);
    }

    public function down(): void
    {
        DB::table('categories')->where('type', 'dish')->update(['type' => 'menu']);
        DB::table('categories')->where('type', 'post')->update(['type' => 'blog']);

        Schema::table('categories', function (Blueprint $table): void {
            $table->dropColumn(['image', 'sort_order', 'is_active', 'meta_title', 'meta_description']);
        });
    }
};
