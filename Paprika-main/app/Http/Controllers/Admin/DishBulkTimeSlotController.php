<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Dish;
use App\Models\DishTimeSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DishBulkTimeSlotController extends Controller
{
    public function edit(Request $request): View
    {
        $ids = $this->validateDishIds($request);

        $branches = Branch::query()->orderBy('sort_order')->orderBy('name')->get();
        $dishes = Dish::query()
            ->whereIn('id', $ids)
            ->with(['timeSlots.branch'])
            ->orderBy('name')
            ->get();

        return view('admin.dishes.bulk-time-slots', [
            'dishIds' => $ids,
            'branches' => $branches,
            'dishes' => $dishes,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $ids = $this->validateDishIds($request);

        $data = $request->validate([
            'branch_id' => ['required', 'exists:branches,id'],
            'mode' => ['required', 'in:replace,add,clear'],
            'time_slot_ids' => ['nullable', 'array'],
            'time_slot_ids.*' => ['integer', 'exists:dish_time_slots,id'],
        ]);

        $branchId = (int) $data['branch_id'];
        $mode = (string) $data['mode'];
        $slotIds = collect($data['time_slot_ids'] ?? [])->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();

        $validSlotIds = DishTimeSlot::query()
            ->where('branch_id', $branchId)
            ->whereIn('id', $slotIds)
            ->pluck('id')
            ->all();

        $dishes = Dish::query()->whereIn('id', $ids)->get();

        foreach ($dishes as $dish) {
            if ($mode === 'clear') {
                $dish->timeSlots()->detach(
                    DishTimeSlot::query()->where('branch_id', $branchId)->pluck('id')->all()
                );

                continue;
            }

            if ($mode === 'replace') {
                $dish->timeSlots()->detach(
                    DishTimeSlot::query()->where('branch_id', $branchId)->pluck('id')->all()
                );
                $dish->timeSlots()->attach($validSlotIds);

                continue;
            }

            if ($mode === 'add') {
                $dish->timeSlots()->syncWithoutDetaching($validSlotIds);
            }
        }

        return redirect()->route('admin.dishes.index')->with('success', 'Đã cập nhật khung giờ cho các món đã chọn.');
    }

    private function validateDishIds(Request $request): array
    {
        return collect((array) $request->input('ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
