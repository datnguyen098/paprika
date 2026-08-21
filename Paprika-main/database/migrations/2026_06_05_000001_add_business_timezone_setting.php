<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        $exists = DB::table('site_settings')->where('key', 'business_timezone')->exists();

        if (! $exists) {
            DB::table('site_settings')->insert([
                'key' => 'business_timezone',
                'value' => 'Europe/Athens',
                'type' => 'text',
                'group' => 'general',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Cache::forget('site_settings');
    }

    public function down(): void
    {
        if (! Schema::hasTable('site_settings')) {
            return;
        }

        DB::table('site_settings')->where('key', 'business_timezone')->delete();
        Cache::forget('site_settings');
    }
};
