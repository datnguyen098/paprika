<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('chat_sessions', 'branch_id')) {
            return;
        }

        Schema::table('chat_sessions', function (Blueprint $table): void {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('chat_sessions', 'branch_id')) {
            return;
        }

        Schema::table('chat_sessions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
