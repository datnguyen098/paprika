<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\SyncsTranslations;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DishTimeSlotRequest;
use App\Models\Branch;
use App\Models\DishTimeSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DishTimeSlotController extends Controller
{
    use SyncsTranslations;

    public function index(Request $request): View
    {
        $branches = Branch::query()->orderBy('sort_order')->orderBy('name')->get();

        $query = DishTimeSlot::query()->with('branch');

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->integer('branch_id'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $slots = $query
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.dish-time-slots.index', compact('slots', 'branches'));
    }

    public function create(): View
    {
        $branches = Branch::query()->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.dish-time-slots.create', [
            'slot' => new DishTimeSlot(['is_active' => true]),
            'branches' => $branches,
        ]);
    }

    public function store(DishTimeSlotRequest $request): RedirectResponse
    {
        $data = $this->normalizedData($request);

        $slot = DishTimeSlot::create($data);
        $this->syncTranslations($request, $slot);

        return redirect()->route('admin.dish-time-slots.index')->with('success', 'Đã tạo khung giờ.');
    }

    public function edit(DishTimeSlot $dishTimeSlot): View
    {
        $branches = Branch::query()->orderBy('sort_order')->orderBy('name')->get();

        $dishTimeSlot->load('translations');

        return view('admin.dish-time-slots.edit', [
            'slot' => $dishTimeSlot,
            'branches' => $branches,
        ]);
    }

    public function update(DishTimeSlotRequest $request, DishTimeSlot $dishTimeSlot): RedirectResponse
    {
        $data = $this->normalizedData($request);

        $dishTimeSlot->update($data);
        $this->syncTranslations($request, $dishTimeSlot);

        return redirect()->route('admin.dish-time-slots.index')->with('success', 'Đã cập nhật khung giờ.');
    }

    public function destroy(DishTimeSlot $dishTimeSlot): RedirectResponse
    {
        $dishTimeSlot->delete();

        return back()->with('success', 'Đã xóa khung giờ.');
    }

    private function normalizedData(DishTimeSlotRequest $request): array
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        return collect($data)->except('translations')->all();
    }
}
