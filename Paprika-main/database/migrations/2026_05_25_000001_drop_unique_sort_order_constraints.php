<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('branches')) {
            Schema::table('branches', function (Blueprint $table): void {
                $table->dropUnique('branches_sort_order_unique');
            });
        }

        if (Schema::hasTable('promotions')) {
            Schema::table('promotions', function (Blueprint $table): void {
                $table->dropUnique('promotions_placement_sort_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('branches')) {
            $this->normalizeBranchSortOrders();

            Schema::table('branches', function (Blueprint $table): void {
                $table->unique('sort_order', 'branches_sort_order_unique');
            });
        }

        if (Schema::hasTable('promotions')) {
            $this->normalizePromotionSortOrders();

            Schema::table('promotions', function (Blueprint $table): void {
                $table->unique(['placement', 'sort_order'], 'promotions_placement_sort_unique');
            });
        }
    }

    private function normalizeBranchSortOrders(): void
    {
        DB::table('branches')
            ->select(['id'])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->values()
            ->each(function ($branch, int $index): void {
                DB::table('branches')
                    ->where('id', $branch->id)
                    ->update(['sort_order' => $index + 1]);
            });
    }

    private function normalizePromotionSortOrders(): void
    {
        DB::table('promotions')
            ->select(['id', 'placement'])
            ->orderBy('placement')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('placement')
            ->each(function ($promotions): void {
                $promotions->values()->each(function ($promotion, int $index): void {
                    DB::table('promotions')
                        ->where('id', $promotion->id)
                        ->update(['sort_order' => $index]);
                });
            });
    }
};
