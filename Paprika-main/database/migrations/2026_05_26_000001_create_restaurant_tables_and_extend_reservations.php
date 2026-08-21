<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurant_tables', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->unsignedTinyInteger('seats')->default(2);
            $table->string('zone')->nullable();
            $table->string('status')->default('active')->index();
            $table->boolean('is_joinable')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->unique(['branch_id', 'code']);
            $table->index(['branch_id', 'status', 'sort_order']);
        });

        Schema::table('reservations', function (Blueprint $table): void {
            $table->foreignId('table_id')->nullable()->after('branch_id')->constrained('restaurant_tables')->nullOnDelete();
            $table->unsignedSmallInteger('duration_minutes')->default(90)->after('reservation_time');
            $table->timestamp('hold_expires_at')->nullable()->after('confirmed_at');
            $table->timestamp('seated_at')->nullable()->after('hold_expires_at');
            $table->timestamp('no_show_at')->nullable()->after('seated_at');
            $table->string('source')->default('web')->after('contact_attempts')->index();

            $table->index(['branch_id', 'reservation_date', 'reservation_time']);
            $table->index(['table_id', 'reservation_date', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropIndex(['branch_id', 'reservation_date', 'reservation_time']);
            $table->dropIndex(['table_id', 'reservation_date', 'status']);
            $table->dropIndex(['source']);
            $table->dropConstrainedForeignId('table_id');
            $table->dropColumn([
                'duration_minutes',
                'hold_expires_at',
                'seated_at',
                'no_show_at',
                'source',
            ]);
        });

        Schema::dropIfExists('restaurant_tables');
    }
};
