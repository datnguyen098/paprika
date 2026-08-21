<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dish_option_group_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dish_option_group_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['dish_option_group_id', 'locale'], 'dogt_parent_locale_unique');
        });

        Schema::create('dish_option_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dish_option_id')->constrained()->cascadeOnDelete();
            $table->string('locale', 8);
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            $table->unique(['dish_option_id', 'locale'], 'dot_parent_locale_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dish_option_translations');
        Schema::dropIfExists('dish_option_group_translations');
    }
};
