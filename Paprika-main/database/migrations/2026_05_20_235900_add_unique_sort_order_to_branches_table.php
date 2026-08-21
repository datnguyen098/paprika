<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('branches')) {
            return;
        }

        $branches = DB::table('branches')
            ->select('id', 'sort_order')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $currentOrder = 1;
        foreach ($branches as $branch) {
            DB::table('branches')
                ->where('id', $branch->id)
                ->update(['sort_order' => $currentOrder]);
            $currentOrder++;
        }

        Schema::table('branches', function (Blueprint $table): void {
            $table->unique('sort_order', 'branches_sort_order_unique');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('branches')) {
            return;
        }

        Schema::table('branches', function (Blueprint $table): void {
            $table->dropUnique('branches_sort_order_unique');
        });
    }
};
