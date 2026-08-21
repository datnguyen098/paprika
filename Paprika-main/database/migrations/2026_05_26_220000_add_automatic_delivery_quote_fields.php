<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table): void {
            $table->boolean('auto_delivery_quote_enabled')->default(false)->after('accepts_delivery_orders');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->decimal('delivery_latitude', 10, 7)->nullable()->after('delivery_address');
            $table->decimal('delivery_longitude', 10, 7)->nullable()->after('delivery_latitude');
            $table->string('delivery_place_id')->nullable()->after('delivery_longitude');
            $table->string('delivery_quote_source')->nullable()->after('delivery_zone_label');
        });

        Schema::table('shipments', function (Blueprint $table): void {
            $table->decimal('latitude', 10, 7)->nullable()->after('address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('place_id')->nullable()->after('longitude');
            $table->string('quote_source')->nullable()->after('zone_label');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table): void {
            $table->dropColumn(['latitude', 'longitude', 'place_id', 'quote_source']);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['delivery_latitude', 'delivery_longitude', 'delivery_place_id', 'delivery_quote_source']);
        });

        Schema::table('branches', function (Blueprint $table): void {
            $table->dropColumn('auto_delivery_quote_enabled');
        });
    }
};
