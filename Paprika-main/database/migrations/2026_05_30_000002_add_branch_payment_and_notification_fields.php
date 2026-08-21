<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table): void {
            $table->boolean('accepts_offline_payment')->default(true)->after('accepts_delivery_orders');
            $table->string('order_notification_email', 255)->nullable()->after('accepts_offline_payment');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table): void {
            $table->dropColumn(['accepts_offline_payment', 'order_notification_email']);
        });
    }
};
