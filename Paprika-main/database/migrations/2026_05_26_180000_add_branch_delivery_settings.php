<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table): void {
            $table->boolean('accepts_online_orders')->default(true)->after('zalo_url');
            $table->boolean('accepts_pickup_orders')->default(true)->after('accepts_online_orders');
            $table->boolean('accepts_delivery_orders')->default(true)->after('accepts_pickup_orders');
            $table->unsignedInteger('delivery_min_order_amount')->default(0)->after('accepts_delivery_orders');
            $table->unsignedInteger('delivery_free_order_amount')->nullable()->after('delivery_min_order_amount');
            $table->decimal('delivery_max_distance_km', 6, 2)->nullable()->after('delivery_free_order_amount');
            $table->decimal('delivery_origin_latitude', 10, 7)->nullable()->after('delivery_max_distance_km');
            $table->decimal('delivery_origin_longitude', 10, 7)->nullable()->after('delivery_origin_latitude');
            $table->text('delivery_note')->nullable()->after('delivery_origin_longitude');
        });

        Schema::create('branch_delivery_zones', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            $table->decimal('min_distance_km', 6, 2)->default(0);
            $table->decimal('max_distance_km', 6, 2)->nullable();
            $table->unsignedInteger('fee')->default(0);
            $table->string('label')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['branch_id', 'is_active', 'sort_order']);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->decimal('delivery_distance_km', 6, 2)->nullable()->after('delivery_address');
            $table->string('delivery_zone_label')->nullable()->after('delivery_distance_km');
            $table->boolean('delivery_fee_overridden')->default(false)->after('delivery_zone_label');
        });

        Schema::table('shipments', function (Blueprint $table): void {
            $table->decimal('distance_km', 6, 2)->nullable()->after('fee');
            $table->string('zone_label')->nullable()->after('distance_km');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table): void {
            $table->dropColumn(['distance_km', 'zone_label']);
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn(['delivery_distance_km', 'delivery_zone_label', 'delivery_fee_overridden']);
        });

        Schema::dropIfExists('branch_delivery_zones');

        Schema::table('branches', function (Blueprint $table): void {
            $table->dropColumn([
                'accepts_online_orders',
                'accepts_pickup_orders',
                'accepts_delivery_orders',
                'delivery_min_order_amount',
                'delivery_free_order_amount',
                'delivery_max_distance_km',
                'delivery_origin_latitude',
                'delivery_origin_longitude',
                'delivery_note',
            ]);
        });
    }
};
