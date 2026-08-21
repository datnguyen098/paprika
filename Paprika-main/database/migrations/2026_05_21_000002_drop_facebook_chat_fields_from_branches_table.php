<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table): void {
            if (Schema::hasColumn('branches', 'facebook_page_id')) {
                $table->dropColumn('facebook_page_id');
            }

            if (Schema::hasColumn('branches', 'messenger_url')) {
                $table->dropColumn('messenger_url');
            }
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table): void {
            if (! Schema::hasColumn('branches', 'messenger_url')) {
                $table->string('messenger_url')->nullable()->after('facebook_url');
            }

            if (! Schema::hasColumn('branches', 'facebook_page_id')) {
                $table->string('facebook_page_id')->nullable()->after('messenger_url');
            }
        });
    }
};
