<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->text('admin_note')->nullable()->after('status');
        });

        Schema::table('contacts', function (Blueprint $table): void {
            $table->string('status')->default('new')->after('message')->index();
            $table->text('admin_note')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table): void {
            $table->dropColumn('admin_note');
        });

        Schema::table('contacts', function (Blueprint $table): void {
            $table->dropColumn(['status', 'admin_note']);
        });
    }
};
