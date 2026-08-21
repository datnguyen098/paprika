<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('site_settings')->updateOrInsert(
            ['key' => 'disable_offline_payment'],
            [
                'value' => '0',
                'type' => 'boolean',
                'group' => 'general',
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('site_settings')->where('key', 'disable_offline_payment')->delete();
    }
};
