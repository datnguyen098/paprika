<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        DB::table('site_settings')->updateOrInsert(
            ['key' => 'open_days'],
            [
                'value' => '1,2,3,4,5,6,0',
                'type' => 'text',
                'group' => 'general',
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        Cache::forget('site_settings');
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        DB::table('site_settings')->where('key', 'open_days')->delete();
        Cache::forget('site_settings');
    }
};
