<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('navigation_menu_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('navigation_menu_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8)->index();
            $table->string('title');
            $table->string('url')->nullable();
            $table->timestamps();
            $table->unique(['navigation_menu_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('navigation_menu_translations');
    }
};
