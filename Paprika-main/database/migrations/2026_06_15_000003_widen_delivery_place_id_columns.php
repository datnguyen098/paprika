<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasColumn('orders', 'delivery_place_id')) {
            DB::statement('ALTER TABLE `orders` MODIFY `delivery_place_id` TEXT NULL');
        }

        if (Schema::hasColumn('shipments', 'place_id')) {
            DB::statement('ALTER TABLE `shipments` MODIFY `place_id` TEXT NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (Schema::hasColumn('orders', 'delivery_place_id')) {
            DB::statement('ALTER TABLE `orders` MODIFY `delivery_place_id` VARCHAR(255) NULL');
        }

        if (Schema::hasColumn('shipments', 'place_id')) {
            DB::statement('ALTER TABLE `shipments` MODIFY `place_id` VARCHAR(255) NULL');
        }
    }
};
