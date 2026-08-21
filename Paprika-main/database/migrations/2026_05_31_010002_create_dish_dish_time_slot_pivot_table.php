<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dish_dish_time_slot', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('dish_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dish_time_slot_id')->constrained('dish_time_slots')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['dish_id', 'dish_time_slot_id']);
            $table->index(['dish_time_slot_id', 'dish_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dish_dish_time_slot');
    }
};
