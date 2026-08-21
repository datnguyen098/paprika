<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->unsignedInteger('base_unit_price')->default(0)->after('dish_name');
            $table->integer('options_total')->default(0)->after('base_unit_price');
            $table->json('options_snapshot')->nullable()->after('line_total');
            $table->text('customization_note')->nullable()->after('options_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn([
                'base_unit_price',
                'options_total',
                'options_snapshot',
                'customization_note',
            ]);
        });
    }
};
