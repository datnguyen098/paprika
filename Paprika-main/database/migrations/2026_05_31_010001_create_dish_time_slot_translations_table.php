<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dish_time_slot_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dish_time_slot_id')->constrained('dish_time_slots')->cascadeOnDelete();
            $table->string('locale', 10)->index();
            $table->string('name');
            $table->timestamps();

            $table->unique(['dish_time_slot_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dish_time_slot_translations');
    }
};
