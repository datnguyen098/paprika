<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->normalizeSortOrders();

        Schema::table('promotions', function (Blueprint $table): void {
            $table->unique(['placement', 'sort_order'], 'promotions_placement_sort_unique');
        });
    }

    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table): void {
            $table->dropUnique('promotions_placement_sort_unique');
        });
    }

    private function normalizeSortOrders(): void
    {
        DB::table('promotions')
            ->select(['id', 'placement', 'sort_order'])
            ->orderBy('placement')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->groupBy('placement')
            ->each(function ($rows): void {
                $usedOrders = [];
                $nextOrder = 0;

                foreach ($rows as $row) {
                    $sortOrder = max(0, (int) $row->sort_order);

                    if (in_array($sortOrder, $usedOrders, true)) {
                        while (in_array($nextOrder, $usedOrders, true)) {
                            $nextOrder++;
                        }

                        $sortOrder = $nextOrder;
                        DB::table('promotions')->where('id', $row->id)->update(['sort_order' => $sortOrder]);
                    }

                    $usedOrders[] = $sortOrder;
                    $nextOrder = max($nextOrder, $sortOrder + 1);
                }
            });
    }
};
