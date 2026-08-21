<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\RestaurantTable;
use App\Support\ReservationTableAvailability;
use App\Services\SeoService;
use App\Support\OpenDays;
use App\Support\OpeningHours;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class ReservationController extends Controller
{
    public function create(Request $request): View
    {
        $branches = Branch::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $selectedBranch = $branches->first(
            fn (Branch $branch): bool => (string) $branch->id === (string) $request->query('branch')
                || $branch->slug === $request->query('branch')
        ) ?: $branches->first();

        $openingHours = OpeningHours::fromBranch($selectedBranch);
        $businessToday = OpenDays::nextOpenDate($selectedBranch)->toDateString();
        $tables = RestaurantTable::query()
            ->where('branch_id', $selectedBranch?->id)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get();

        $seo = SeoService::page(
            is_english() ? 'Book a table | '.localized_setting('restaurant_name', 'Paprika') : 'Đặt bàn | '.localized_setting('restaurant_name', 'Paprika'),
            is_english() ? 'Reserve a table for a fresh Vietnamese restaurant experience.' : 'Đặt bàn tại Paprika cho bữa ăn Việt Nam ấm áp ở Patras.',
            is_english() ? 'reservation, Vietnamese restaurant, book table' : 'đặt bàn Paprika, nhà hàng Việt Nam Patras, đặt bàn nhà hàng',
            localized_route('reservations.create')
        );

        return view('storefront.reservations.create', compact('branches', 'selectedBranch', 'openingHours', 'businessToday', 'tables', 'seo'));
    }

    public function availability(Request $request): JsonResponse
    {
        $data = $request->validate([
            'branch_id' => ['required', 'integer', 'exists:branches,id'],
            'reservation_date' => ['required', 'date_format:Y-m-d'],
            'reservation_time' => ['required', 'date_format:H:i'],
            'guests' => ['required', 'integer', 'min:1', 'max:40'],
        ]);

        $branch = Branch::query()->active()->findOrFail($data['branch_id']);
        if (! OpenDays::isOpenOn($data['reservation_date'], $branch)) {
            return response()->json([
                'tables' => RestaurantTable::query()
                    ->where('branch_id', $branch->id)
                    ->orderBy('sort_order')
                    ->orderBy('code')
                    ->get()
                    ->map(fn (RestaurantTable $table): array => [
                        'id' => $table->id,
                        'code' => $table->code,
                        'name' => $table->name,
                        'seats' => $table->seats,
                        'zone' => $table->zone,
                        'status' => $table->status,
                        'available' => false,
                        'reason' => 'Ngày đóng cửa',
                    ]),
            ]);
        }

        $availableIds = ReservationTableAvailability::availableTables(
            $branch->id,
            $data['reservation_date'],
            $data['reservation_time'],
            (int) $data['guests'],
            null,
            (int) setting('reservation_duration_minutes', ReservationTableAvailability::DEFAULT_DURATION_MINUTES),
        )->pluck('id');

        $tables = RestaurantTable::query()
            ->where('branch_id', $branch->id)
            ->orderBy('sort_order')
            ->orderBy('code')
            ->get()
            ->map(fn (RestaurantTable $table): array => [
                'id' => $table->id,
                'code' => $table->code,
                'name' => $table->name,
                'seats' => $table->seats,
                'zone' => $table->zone,
                'status' => $table->status,
                'available' => $availableIds->contains($table->id),
                'reason' => $table->status !== 'active'
                    ? $table->statusLabel()
                    : ($table->seats < (int) $data['guests'] ? 'Không đủ ghế' : ($availableIds->contains($table->id) ? null : 'Đã có khách đặt')),
            ]);

        return response()->json(['tables' => $tables]);
    }
}
